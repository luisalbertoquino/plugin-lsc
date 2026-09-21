#!/usr/bin/env bash
# Empaqueta el plugin en build/accesibilidad-lsc.zip, listo para
# subir desde Plugins > Añadir nuevo > Subir plugin en WordPress.
set -euo pipefail

cd "$(dirname "$0")"

SLUG="accesibilidad-lsc"
BUILD_DIR="build"
STAGE_DIR="${BUILD_DIR}/${SLUG}"
ZIP_PATH="${BUILD_DIR}/${SLUG}.zip"

rm -rf "${STAGE_DIR}" "${ZIP_PATH}"
mkdir -p "${STAGE_DIR}"

# Copia solo los archivos que el plugin necesita en producción.
cp -r assets "${STAGE_DIR}/"
cp -r includes "${STAGE_DIR}/"
cp accesibilidad-lsc.php "${STAGE_DIR}/"
cp readme.txt "${STAGE_DIR}/"

if command -v zip >/dev/null 2>&1; then
	( cd "${BUILD_DIR}" && zip -r -q "${SLUG}.zip" "${SLUG}" )
elif command -v python >/dev/null 2>&1 || command -v python3 >/dev/null 2>&1; then
	# Sin `zip` disponible (Git Bash en Windows no lo trae por defecto):
	# se arma el zip con el módulo zipfile de Python, que sí genera rutas
	# internas con "/" (formato ZIP estándar). Compress-Archive de
	# PowerShell escribe "\" en las rutas internas, lo cual WordPress
	# no siempre reconoce al extraer el plugin en el servidor.
	PY="$(command -v python || command -v python3)"
	"${PY}" -c "
import zipfile, pathlib, sys

build_dir = pathlib.Path('${BUILD_DIR}')
stage_dir = pathlib.Path('${STAGE_DIR}')
zip_path = build_dir / '${SLUG}.zip'

with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zf:
    for path in sorted(stage_dir.rglob('*')):
        if path.is_file():
            arcname = path.relative_to(build_dir).as_posix()
            zf.write(path, arcname)
"
else
	# Último recurso: Compress-Archive de PowerShell (rutas con "\").
	STAGE_WIN="$(cd "${STAGE_DIR}" && pwd -W)"
	ZIP_WIN="$(cd "${BUILD_DIR}" && pwd -W)/${SLUG}.zip"
	powershell.exe -NoProfile -Command "Compress-Archive -Path '${STAGE_WIN}' -DestinationPath '${ZIP_WIN}' -Force"
fi

rm -rf "${STAGE_DIR}"

echo "Listo: ${ZIP_PATH}"
