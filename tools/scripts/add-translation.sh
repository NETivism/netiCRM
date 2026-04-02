#! /bin/bash
# Append a translation to civicrm.po and push to Transifex, then regenerate .mo.
# Usage: add-translation.sh [--push] <source_string> <translated_string> [language]
#
# Options:
#   --push    Push translation to Transifex after appending
#
# Arguments:
#   source_string     - The English source string (msgid)
#   translated_string - The translated text (msgstr)
#   language          - Language code, defaults to zh_TW
#
# Examples:
#   add-translation.sh "Failed Donation Date (From)" "捐款失敗日期（起）"
#   add-translation.sh --push "Hello %1" "你好 %1" zh_TW

CALLEDPATH=$(dirname "$0")
CIVICRMPATH=$(cd "$CALLEDPATH/../../" && pwd)

PUSH=0
ARGS=()

for arg in "$@"; do
  case "$arg" in
    --push) PUSH=1 ;;
    *)      ARGS+=("$arg") ;;
  esac
done

STRING="${ARGS[0]}"
TRANSLATION="${ARGS[1]}"
LANGUAGE="${ARGS[2]:-zh_TW}"

PO_FILE="$CIVICRMPATH/l10n/$LANGUAGE/civicrm.po"
MO_FILE="$CIVICRMPATH/l10n/$LANGUAGE/LC_MESSAGES/civicrm.mo"

if [ -z "$STRING" ] || [ -z "$TRANSLATION" ]; then
  echo "Usage: $0 [--push] <source_string> <translated_string> [language]"
  echo "Example: $0 'Failed Donation Date (From)' '捐款失敗日期（起）'"
  exit 1
fi

if [ ! -f "$PO_FILE" ]; then
  echo "Error: PO file not found at ${PO_FILE}"
  exit 1
fi

# Escape backslashes then double quotes for PO format (e.g. \ -> \\, " -> \")
ESCAPED_STRING=$(printf '%s' "$STRING" | sed 's/\\/\\\\/g; s/"/\\"/g')
ESCAPED_TRANSLATION=$(printf '%s' "$TRANSLATION" | sed 's/\\/\\\\/g; s/"/\\"/g')

# Find source files containing the string (exclude l10n dir)
SOURCES=$(grep -rl --exclude-dir=l10n \
  --include="*.php" --include="*.tpl" --include="*.module" --include="*.inc" \
  "$STRING" "$CIVICRMPATH" 2>/dev/null \
  | sed "s|${CIVICRMPATH}/||" \
  | tr '\n' ' ' \
  | sed 's/ $//')

# Check for duplicate
if grep -qF "msgid \"${ESCAPED_STRING}\"" "$PO_FILE"; then
  echo "Warning: String already exists in PO file, skipping append."
  echo "  String: ${STRING}"
else
  if [ -n "$SOURCES" ]; then
    printf '\n#: %s\nmsgid "%s"\nmsgstr "%s"\n' "$SOURCES" "$ESCAPED_STRING" "$ESCAPED_TRANSLATION" >> "$PO_FILE"
  else
    printf '\nmsgid "%s"\nmsgstr "%s"\n' "$ESCAPED_STRING" "$ESCAPED_TRANSLATION" >> "$PO_FILE"
  fi
  echo "Appended to PO: ${PO_FILE}"
  [ -n "$SOURCES" ] && echo "  Sources: ${SOURCES}"
fi

if [ "$PUSH" -eq 1 ]; then
  echo "Pushing translation (${LANGUAGE}) to Transifex ..."
  cd "$CIVICRMPATH"
  tx push -t -l "$LANGUAGE"
fi

echo "Generating MO file ..."
if [ ! -d "$CIVICRMPATH/l10n/$LANGUAGE/LC_MESSAGES" ]; then
  mkdir -p "$CIVICRMPATH/l10n/$LANGUAGE/LC_MESSAGES"
fi
msgfmt "$PO_FILE" -o "$MO_FILE"
echo "Generated: ${MO_FILE}"
echo "Done."
