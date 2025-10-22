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

### 1. Importar Fitxer RTF

1. Ves a **Catàleg Fires** al menú d'administració
2. Puja el fitxer `catalogo.rtf`
3. Fes clic a **Crear Categories a WordPress**
4. Fes clic a **Importar Posts com a Esborranys** (opcional)

### 2. Configurar Categories

El plugin utilitza una **taxonomia personalitzada** (Categories del Catàleg) separada de les categories de WordPress.

Ves a **Entrades → Categories Catàleg** per:
- Crear noves categories
- Editar les categories importades
- Afegir descripcions

Les categories són planes (no jeràrquiques) i cada una representa una secció del catàleg.
### 3. Assignar Posts a Categories

1. Edita posts existents de WordPress
2. A la dreta veuràs el selector **"Categories Catàleg"**
3. Assigna el post a una o més categories del catàleg
4. Al metabox "Configuració Catàleg" pots:
   - Marcar el post com a **favorit** ⭐ (es mostrarà a la pàgina principal)
   - Definir l'**ordre de visualització** (número menor = apareix primer)

### 4. Mostrar el Catàleg

#### Pàgina Principal (Favorits)

Utilitza el shortcode `[cataleg_festes]` per mostrar totes les categories amb els seus favorits:

```
[cataleg_festes]
```

**Paràmetres:**
- `columnas` - Número de columnes (per defecte: 4)
- `max_favoritos` - Màxim de favorits per categoria (per defecte: 4)

**Exemples:**
```
[cataleg_festes columnas="3" max_favoritos="6"]
```

#### Pàgina de Categoria (Tots els Posts)

Les categories són **enllaçables automàticament**. Quan un usuari fa clic a una categoria, WordPress mostrarà TOTS els posts d'aquella categoria.

També pots usar el shortcode `[cataleg_categoria]` manualment:

```
[cataleg_categoria slug="grups-de-musica"]
```

o

```
[cataleg_categoria id="123"]
```
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

### Versió 2.1.0
- **Taxonomia personalitzada**: Sistema de categories independent de WordPress
- **Vista principal amb favorits**: Mostra 4 posts destacats per categoria
- **Pàgines de categoria**: Enllaç a vista completa amb TOTS els posts
- **Disseny de targetes**: Grid responsive amb hover effects
- **Dos shortcodes**: `[cataleg_festes]` i `[cataleg_categoria]`
- **Millor experiència d'usuari**: Navegació fluida entre favorits i tots

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
