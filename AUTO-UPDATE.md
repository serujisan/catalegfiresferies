# Configurar Auto-Update del Plugin

El plugin está configurado para recibir actualizaciones automáticas desde GitHub.

## 📋 Pasos para configurar:

### 1. Crear repositorio en GitHub

1. Ve a https://github.com/new
2. Crea un repositorio llamado `catalegfiresferies`
3. Puede ser público o privado

### 2. Subir el código a GitHub

```bash
cd /Users/seruji/Local\ Sites/festesmajors/plugins/catalegfiresferies
git init
git add .
git commit -m "v2.0.0 - Auto-update enabled"
git branch -M main
git remote add origin https://github.com/TU_USUARIO/catalegfiresferies.git
git push -u origin main
```

### 3. Actualizar la URL en el plugin

Edita el archivo `catalegfiresferies.php` línea 29:

```php
// ANTES:
'https://github.com/USUARIO/catalegfiresferies/',

// DESPUÉS (cambia USUARIO por tu nombre de usuario):
'https://github.com/sergimaneja/catalegfiresferies/',
```

### 4. Crear releases en GitHub

Cuando quieras publicar una actualización:

```bash
# 1. Actualiza la versión en catalegfiresferies.php
# Version: 2.1.0

# 2. Commit y push
git add .
git commit -m "v2.1.0 - Nueva funcionalidad"
git push

# 3. Crear release en GitHub
git tag v2.1.0
git push origin v2.1.0
```

O desde la web de GitHub:
1. Ve a tu repositorio → Releases → Create a new release
2. Tag version: `v2.1.0`
3. Release title: `Version 2.1.0`
4. Describe los cambios
5. Publish release

### 5. ¡Listo!

WordPress detectará automáticamente las actualizaciones desde GitHub y las mostrará en el panel de plugins.

## 🔒 Para repositorios privados

Si tu repo es privado, necesitas un token de acceso:

1. Ve a GitHub → Settings → Developer settings → Personal access tokens
2. Generate new token (classic)
3. Selecciona scope: `repo`
4. Copia el token

Descomenta y actualiza en `catalegfiresferies.php`:

```php
$updateChecker->setAuthentication('ghp_TuTokenAqui');
```

## 🌐 Opción alternativa: Servidor propio

Si prefieres alojar las actualizaciones en tu propio servidor:

1. Sube el ZIP del plugin a: `https://tuservidor.com/plugins/catalegfiresferies.zip`
2. Crea un archivo JSON en: `https://tuservidor.com/plugins/catalegfiresferies.json`

```json
{
  "version": "2.1.0",
  "download_url": "https://tuservidor.com/plugins/catalegfiresferies.zip",
  "requires": "5.0",
  "tested": "6.4",
  "sections": {
    "description": "Plugin para gestionar catàleg de fires i fèries",
    "changelog": "<h4>2.1.0</h4><ul><li>Nueva funcionalidad</li></ul>"
  }
}
```

3. Cambia en el plugin:

```php
$updateChecker = PucFactory::buildUpdateChecker(
    'https://tuservidor.com/plugins/catalegfiresferies.json',
    __FILE__,
    'catalegfiresferies'
);
```

## ℹ️ Documentación completa

https://github.com/YahnisElsts/plugin-update-checker
