#! /bin/bash
CALLEDPATH=`dirname $0`
CIVICRMPATH=`cd $CALLEDPATH/../../ && pwd`

# Step 1: Pull latest translations to ensure local PO is up to date
echo "=== Step 1/3: Pulling latest translations from Transifex ==="
"$CALLEDPATH/pull-translation.sh"
echo ""

# Step 2: Sync any orphaned msgids from PO back into local POT before pushing
echo "=== Step 2/3: Checking for orphaned msgids (sync-pot) ==="
"$CALLEDPATH/sync-pot.sh"
if [ $? -ne 0 ]; then
  echo "Error: sync-pot.sh failed. Aborting push."
  exit 1
fi
echo ""

# Step 3: Push source to Transifex
echo "=== Step 3/3: Pushing source to Transifex ==="
cd $CIVICRMPATH
tx push -s
