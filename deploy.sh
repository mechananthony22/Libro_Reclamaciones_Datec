#!/bin/bash
set -e

echo "=== Desplegando Datec Libro en Producción ==="

# 1. Asegurar que existe el archivo .env
if [ ! -f .env ]; then
    echo "Error: No se encontró el archivo .env en este directorio."
    echo "Copia .env.production.example a .env y configura tus credenciales antes de continuar."
    exit 1
fi

# 2. Reconstruir imágenes y levantar servicios de DatecLibro en segundo plano
echo "1/3 Construyendo contenedores y actualizando aplicación..."
docker compose up -d --build

# 3. Esperar a que el contenedor principal esté listo
echo "2/3 Verificando estado de los servicios..."
sleep 5

# 4. Mostrar estado final de los contenedores de DatecLibro
echo "3/3 Estado de contenedores DatecLibro:"
docker compose ps

echo "=== ¡Despliegue completado con éxito! ==="
echo "Para ver logs en vivo ejecuta: docker compose logs -f app"

