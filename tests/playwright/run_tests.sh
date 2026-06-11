#!/bin/bash

# ==============================================================================
# Playwright 循序測試腳本
# 說明：此腳本會嚴格依照陣列中的順序，逐一啟動 Playwright 測試。
# 若途中遇到任何一個測試失敗，腳本將會立即中止，不會繼續執行後續測試。
# ==============================================================================

# 1. 定義測試檔案陣列（請保持你需要的絕對順序）
TEST_FILES=(
  "tests/batch_action.spec.js"
  "tests/page.spec.js"
  "tests/add_contact.spec.js"
  "tests/add_contribution_page.spec.js"
  "tests/add_event.spec.js"
  "tests/edit_contact.spec.js"
  "tests/custom_data.spec.js"
  "tests/contribution_allpay.spec.js"
  "tests/contribution_allpay_atm.spec.js"
  "tests/contribution_allpay_barcode.spec.js"
  "tests/contribution_spgateway.spec.js"
  "tests/new_contribution.spec.js"
  "tests/advanced_search.spec.js"
  "tests/add_group.spec.js"
  "tests/check_membership.spec.js"
  "tests/import.spec.js"
  "tests/event_normal_register.spec.js"
  "tests/event_limit_nowait_register.spec.js"
  "tests/event_limit_wait_register.spec.js"
  "tests/event_limit_approval_register.spec.js"
  "tests/event_unlimit_approval_register.spec.js"
  "tests/event_participant.spec.js"
  "tests/edit_mailing.spec.js"
  "tests/contribution_booster.spec.js"
  "tests/report_check.spec.js"
)

echo "🚀 開始循序執行 Playwright 測試 (共 ${#TEST_FILES[@]} 個檔案)..."
echo "================================================================="

# 2. 使用迴圈依序執行
for FILE in "${TEST_FILES[@]}"; do
  echo ""
  echo "▶️ 正在執行: $FILE"
  
  # 執行 playwright 測試
  npx playwright test "$FILE"
  
  # 3. 檢查執行結果
  # $? 會捕捉上一個指令（也就是 playwright test）的結束代碼
  # 0 代表成功，非 0 代表發生錯誤或失敗
  if [ $? -ne 0 ]; then
    echo ""
    echo "❌ 錯誤：測試在執行『 $FILE 』時失敗！"
    echo "🛑 已自動中止後續測試，避免發生資料不一致的連鎖錯誤。"
    exit 1 # 退出腳本並回傳錯誤代碼
  fi

done

echo ""
echo "================================================================="
echo "✅ 恭喜！所有測試均已依序順利執行完畢！"
