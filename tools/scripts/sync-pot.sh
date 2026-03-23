#! /bin/bash
# Sync msgids that exist in the remote translation (zh_TW PO) but are missing
# from the local civicrm.pot. This resolves conflicts detected by add-source-string.sh.
#
# Workflow:
#   1. Detect orphaned msgids via msgmerge + msgattrib --only-obsolete
#   2. Use msggrep to extract full entries (with #: comments) from original PO
#   3. Preview entries and ask for confirmation
#   4. Append to civicrm.pot (with empty msgstr, POT format)
#
# After syncing, run add-source-string.sh again or push-source.sh to push.
#
# Usage: sync-pot.sh

CALLEDPATH=$(dirname "$0")
CIVICRMPATH=$(cd "$CALLEDPATH/../../" && pwd)
POT_FILE="$CIVICRMPATH/l10n/pot/civicrm.pot"
PO_FILE="$CIVICRMPATH/l10n/zh_TW/civicrm.po"

if [ ! -f "$POT_FILE" ]; then
  echo "Error: POT file not found at ${POT_FILE}"
  exit 1
fi

if [ ! -f "$PO_FILE" ]; then
  echo "Error: PO file not found at ${PO_FILE}"
  exit 1
fi

# Step 1: Find orphaned msgids — in PO but not in local POT.
# msgmerge merges PO against POT; entries missing from POT become obsolete (#~).
TEMP_MERGED=$(mktemp --suffix=.po)
msgmerge --no-fuzzy-matching --quiet "$PO_FILE" "$POT_FILE" -o "$TEMP_MERGED" 2>/dev/null

# Extract orphaned msgid decoded strings (strip #~ msgid prefix and surrounding quotes)
ORPHANED_IDS=$(msgattrib --only-obsolete "$TEMP_MERGED" \
  | grep '^#~ msgid "' | grep -v '^#~ msgid ""$' \
  | sed 's/^#~ msgid "//; s/"$//')
rm -f "$TEMP_MERGED"

ORPHANED_REAL=$(echo "$ORPHANED_IDS" | grep -c .)

if [ -z "$ORPHANED_IDS" ] || [ "$ORPHANED_REAL" -eq 0 ]; then
  echo "No orphaned msgids found. Local POT is already in sync with remote translation."
  exit 0
fi

# Step 2: Use msggrep to extract full entries (including #: source comments) from
# the original PO file. msgattrib --only-obsolete drops #: lines, so we re-fetch
# from source. Run msggrep once per orphaned msgid, then merge with msgcat.
TEMP_EXTRACTED=$(mktemp --suffix=.po)
TEMP_PARTS=()
while IFS= read -r msgid_str; do
  TEMP_PART=$(mktemp --suffix=.po)
  msggrep --msgid -F -e "$msgid_str" "$PO_FILE" > "$TEMP_PART" 2>/dev/null
  TEMP_PARTS+=("$TEMP_PART")
done <<< "$ORPHANED_IDS"
msgcat --use-first "${TEMP_PARTS[@]}" > "$TEMP_EXTRACTED" 2>/dev/null
rm -f "${TEMP_PARTS[@]}"

# Step 3: Preview
echo "Found ${ORPHANED_REAL} msgid(s) in remote translation but missing from local civicrm.pot:"
echo ""
awk '
  !header_done && /^msgid ""/ { in_header=1 }
  !header_done && in_header && /^$/ { header_done=1; next }
  !header_done { next }
  /^#:/ || /^msgid "/ { print "  " $0 }
' "$TEMP_EXTRACTED"
echo ""

# Step 4: Ask for confirmation
printf "Add these %s entry/entries to civicrm.pot? [y/N] " "$ORPHANED_REAL"
read -r CONFIRM

if [ "$CONFIRM" != "y" ] && [ "$CONFIRM" != "Y" ]; then
  echo "Aborted."
  rm -f "$TEMP_EXTRACTED"
  exit 0
fi

# Step 5: Append to POT.
# Skip PO file header block (msgid "" and everything before the first blank line after it).
# Clear msgstr values to "" (POT format), handling multi-line msgstr.
printf '\n' >> "$POT_FILE"
awk '
  !header_done && /^msgid ""/ { in_header=1 }
  !header_done && in_header && /^$/ { header_done=1; next }
  !header_done { next }

  /^msgstr / { print "msgstr \"\""; in_msgstr=1; next }
  /^"/ && in_msgstr { next }
  { in_msgstr=0; print }
' "$TEMP_EXTRACTED" >> "$POT_FILE"

rm -f "$TEMP_EXTRACTED"

echo ""
echo "Synced ${ORPHANED_REAL} entry/entries to: ${POT_FILE}"
echo "Next step: run add-source-string.sh or push-source.sh to push to Transifex."
