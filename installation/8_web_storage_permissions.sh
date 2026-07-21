# ===================== WEB STORAGE & PERMISSIONS =====================
echo -e "\n=== Preparing Storage Directories ==="
mkdir -p "${STORAGE_PATH}"/{logs,vectors,uploads/queue/{ingest,process,archive,fail}}
sudo chown -R "${CURRENT_USER}:www-data" "${STORAGE_PATH}"
sudo chmod -R 2775 "${STORAGE_PATH}"