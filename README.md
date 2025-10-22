# Catàleg Fires i Fèries - Plugin WordPress

Plugin per gestionar un catàleg de fires i fèries amb categories jeràrquiques i sistema de favorits.

## Característiques

- ✅ Importació de fitxers RTF amb contingut del catàleg
- ✅ Sistema de categories pare i subcategories
- ✅ Marcar posts com a favorits
- ✅ Ordenació personalitzada de posts
- ✅ Shortcode per mostrar el catàleg
- ✅ Disseny responsive i modern
- ✅ Metabox a l'editor de posts

## Instal·lació

1. Puja la carpeta `catalegfiresferies` al directori `/wp-content/plugins/`
2. Activa el plugin des del menú "Plugins" de WordPress
3. Accedeix a "Catàleg Fires" al menú d'administració

## Ús

### 1. Configurar Categories

Ves a **Entrades → Categories** i crea:
- **Categories pare**: Aquestes seran les seccions principals del catàleg
- **Subcategories**: Assigna-les a les categories pare corresponents

Exemple d'estructura:
```
Fires (categoria pare)
  ├── Fires de tardor (subcategoria)
  └── Fires de primavera (subcategoria)
Fèries (categoria pare)
  ├── Fèries medievals (subcategoria)
  └── Fèries gastronòmiques (subcategoria)
```

### 2. Assignar Posts a Categories

1. Edita o crea posts existents
2. Assigna'ls a les subcategories creades
3. Al metabox "Configuració Catàleg" pots:
   - Marcar el post com a **favorit** ⭐
   - Definir l'**ordre de visualització** (número menor = apareix primer)

### 3. Importar Fitxer RTF

1. Ves a **Catàleg Fires** al menú d'administració
2. Puja el fitxer `catalogo.rtf`
3. El contingut es processarà i guardarà

### 4. Mostrar el Catàleg

Utilitza el shortcode `[cataleg_festes]` a qualsevol pàgina o entrada.

#### Paràmetres del Shortcode

- `categoria` - Slug de la categoria pare per mostrar només una secció
- `mostrar_favoritos` - Mostrar només favorits (`si` o `no`)
- `posts_por_pagina` - Nombre de posts per pàgina (`-1` per mostrar tots)

#### Exemples

Mostrar tot el catàleg:
```
[cataleg_festes]
```

Mostrar només la categoria "Fires":
```
[cataleg_festes categoria="fires"]
```

Mostrar tots els posts (no només favorits) amb paginació:
```
[cataleg_festes mostrar_favoritos="no" posts_por_pagina="10"]
```

## Estructura del Plugin

```
catalegfiresferies/
├── admin/
│   └── admin-page.php       # Pàgina d'administració
├── assets/
│   ├── css/
│   │   ├── frontend.css     # Estilos del frontend
│   │   └── admin.css        # Estilos del admin
│   └── js/
│       ├── frontend.js      # JavaScript del frontend
│       └── admin.js         # JavaScript del admin
├── catalegfiresferies.php   # Arxiu principal del plugin
└── README.md                # Aquest arxiu
```

## Personalització

### Modificar Estilos

Els estilos CSS es troben a:
- Frontend: `assets/css/frontend.css`
- Admin: `assets/css/admin.css`

### Modificar el Shortcode

Pots modificar la funció `cataleg_shortcode()` a `catalegfiresferies.php` per personalitzar el comportament.

## Requisits

- WordPress 5.0 o superior
- PHP 7.4 o superior
- MySQL 5.6 o superior

## Suport

Per preguntes o problemes, contacta amb l'administrador del lloc.

## Autor

**Sergi Maneja**  
Festes Majors de Catalunya  
https://festesmajorsdecatalunya.cat

## Changelog

### Versió 2.0.0
- Parser HTML intel·ligent per extreure estructura del catàleg
- Creació automàtica de categories des del fitxer RTF
- Importació automàtica de posts com a esborranys
- Visualització de categories extretes amb taula detallada
- Millores en la interfície d'administració

### Versió 1.0.0
- Llançament inicial
- Funcionalitat d'importació RTF
- Sistema de categories i favorits
- Shortcode per mostrar el catàleg
