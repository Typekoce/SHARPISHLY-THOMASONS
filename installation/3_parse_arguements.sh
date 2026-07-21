# ===================== PARSE ARGUMENTS =====================
while getopts ":H:u:p:d:r:D:w:v:" opt; do
  case "${opt}" in
    H) DB_HOST="${OPTARG}" ;;
    u) DB_USER="${OPTARG}" ;;
    p) DB_PASS="${OPTARG}" ;;
    d) DB_NAME="${OPTARG}" ;;
    r) MYSQL_ROOT_PASS="${OPTARG}" ;;
    D) DOMAIN="${OPTARG}" ;;
    w) WEB_ROOT="${OPTARG}" ;;
    v) PHP_VERSION="${OPTARG}" ;;
    *) echo "Usage: $0 [-H host] [-u user] [-p pass] [-d db] [-r rootpass] [-D domain] [-w webroot] [-v phpversion]"; exit 1 ;;
  esac
done