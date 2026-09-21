#!/usr/bin/env bash
# Empaqueta el plugin en build/plugin-accesibilidad-lsc.zip, listo para
# subir desde Plugins > Añadir nuevo > Subir plugin en WordPress.
set -euo pipefail

cd "$(dirname "$0")"

SLUG="plugin-accesibilidad-lsc"
BUILD_DIR="build"
STAGE_DIR="${BUILD_DIR}/${SLUG}"
ZIP_PATH="${BUILD_DIR}/${SLUG}.zip"

rm -rf "${STAGE_DIR}" "${ZIP_PATH}"
mkdir -p "${STAGE_DIR}"

# Copia solo los archivos que el plugin necesita en producción.
cp -r assets "${STAGE_DIR}/"
cp -r includes "${STAGE_DIR}/"
cp plugin-accesibilidad-lsc.php "${STAGE_DIR}/"
cp readme.txt "${STAGE_DIR}/"

if command -v zip >/dev/null 2>&1; then
	( cd "${BUILD_DIR}" && zip -r -q "${SLUG}.zip" "${SLUG}" )
else
	# Sin `zip` disponible (Git Bash en Windows no lo trae por defecto):
	# se usa Compress-Archive de PowerShell, ya presente en el sistema.
	STAGE_WIN="$(cd "${STAGE_DIR}" && pwd -W)"
	ZIP_WIN="$(cd "${BUILD_DIR}" && pwd -W)/${SLUG}.zip"
	powershell.exe -NoProfile -Command "Compress-Archive -Path '${STAGE_WIN}' -DestinationPath '${ZIP_WIN}' -Force"
fi

rm -rf "${STAGE_DIR}"

echo "Listo: ${ZIP_PATH}"
