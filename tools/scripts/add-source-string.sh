#! /bin/bash
# Append a single source string to civicrm.pot and push to Transifex.
# Usage: add-source-string.sh [--push] [--pull-translation] <source_string>
#
# Options:
#   --push              Push source to Transifex after appending
#   --pull-translation  Force pull latest translations (ignores 5-minute cache)
#
# Examples:
#   add-source-string.sh "Failed Donation Date (From)"
#   add-source-string.sh --push "Hello %1"
#   add-source-string.sh --push --pull-translation "Hello %1"

CALLEDPATH=$(dirname "$0")
CIVICRMPATH=$(cd "$CALLEDPATH/../../" && pwd)
POT_FILE="$CIVICRMPATH/l10n/pot/civicrm.pot"
PO_FILE="$CIVICRMPATH/l10n/zh_TW/civicrm.po"
PULL_TIMESTAMP_FILE="/tmp/civicrm-pull-translation-$(id -u).last"
PULL_CACHE_SECONDS=300  # 5 minutes

PUSH=0
FORCE_PULL=0
STRING=""

for arg in "$@"; do
  case "$arg" in
    --push)             PUSH=1 ;;
    --pull-translation) FORCE_PULL=1 ;;
    *)
      if [ -z "$STRING" ]; then
        STRING="$arg"
      fi
      ;;
  esac
done

if [ -z "$STRING" ]; then
  echo "Usage: $0 [--push] [--pull-translation] <source_string>"
  echo "Example: $0 'Failed Donation Date (From)'"
  exit 1
fi

if [ ! -f "$POT_FILE" ]; then
  echo "Error: POT file not found at ${POT_FILE}"
  exit 1
fi

# Step 1: Pull latest translations so local PO reflects remote Transifex source.
# Skip if pull-translation.sh was run within 5 minutes, unless --pull-translation is set.
SHOULD_PULL=1
if [ "$FORCE_PULL" -eq 0 ] && [ -f "$PULL_TIMESTAMP_FILE" ]; then
  LAST_PULL=$(cat "$PULL_TIMESTAMP_FILE" 2>/dev/null)
  NOW=$(date +%s)
  if [ -n "$LAST_PULL" ] && [ $((NOW - LAST_PULL)) -lt $PULL_CACHE_SECONDS ]; then
    SHOULD_PULL=0
    echo "Skipping pull-translation.sh (last run was $((NOW - LAST_PULL))s ago, within ${PULL_CACHE_SECONDS}s cache)."
    echo "  Use --pull-translation to force."
    echo ""
  fi
fi

if [ "$SHOULD_PULL" -eq 1 ]; then
  echo "Pulling latest translations from Transifex ..."
  "$CALLEDPATH/pull-translation.sh"
  date +%s > "$PULL_TIMESTAMP_FILE"
  echo ""
fi

# Step 2: Check for orphaned msgids — in PO but not in local POT.
# msgmerge merges PO against POT and marks entries missing from POT as obsolete (#~).
# msgattrib --only-obsolete then extracts those entries.
echo "Checking for conflicts between local POT and remote source ..."
TEMP_PO=$(mktemp --suffix=.po)
msgmerge --no-fuzzy-matching --quiet "$PO_FILE" "$POT_FILE" -o "$TEMP_PO" 2>/dev/null
ORPHANED=$(msgattrib --only-obsolete "$TEMP_PO" | grep '^#~ msgid "' | grep -v '^#~ msgid ""$')
rm -f "$TEMP_PO"

if [ -n "$ORPHANED" ]; then
  echo "Error: The following msgids exist in the remote translation but are missing from local civicrm.pot:"
  echo ""
  echo "$ORPHANED" | sed 's/^#~ msgid "\(.*\)"$/  \1/'
  echo ""
  echo "Please reconcile your local civicrm.pot with the remote source before adding new strings."
  exit 1
fi
echo "  OK: no conflicts found."
echo ""

# Escape backslashes then double quotes for PO format (e.g. \ -> \\, " -> \")
ESCAPED=$(printf '%s' "$STRING" | sed 's/\\/\\\\/g; s/"/\\"/g')

# Find source files containing the string (exclude l10n dir)
SOURCES=$(grep -rl --exclude-dir=l10n \
  --include="*.php" --include="*.tpl" --include="*.module" --include="*.inc" \
  "$STRING" "$CIVICRMPATH" 2>/dev/null \
  | sed "s|${CIVICRMPATH}/||" \
  | tr '\n' ' ' \
  | sed 's/ $//')

# Check for duplicate
if grep -qF "msgid \"${ESCAPED}\"" "$POT_FILE"; then
  echo "Warning: String already exists in POT file, skipping append."
  echo "  String: ${STRING}"
else
  if [ -n "$SOURCES" ]; then
    printf '\n#: %s\nmsgid "%s"\nmsgstr ""\n' "$SOURCES" "$ESCAPED" >> "$POT_FILE"
  else
    printf '\nmsgid "%s"\nmsgstr ""\n' "$ESCAPED" >> "$POT_FILE"
  fi
  echo "Appended to POT: ${POT_FILE}"
  [ -n "$SOURCES" ] && echo "  Sources: ${SOURCES}"
fi

if [ "$PUSH" -eq 1 ]; then
  echo ""
  echo "Pushing source to Transifex ..."
  cd "$CIVICRMPATH"
  tx push -s
  echo "Done."
fi
