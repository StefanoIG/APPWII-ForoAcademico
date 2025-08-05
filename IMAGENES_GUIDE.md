# Manejo de Imágenes en Preguntas - Foro Académico

## 📋 Funcionalidades Implementadas

### 1. Subida de Imágenes en Preguntas

El sistema ahora soporta la integración completa de imágenes en las preguntas:

- **Subida directa** con archivos adjuntos
- **Integración con Markdown** automática
- **Procesamiento de placeholders** para imágenes
- **Optimización automática** de imágenes

### 2. Endpoints Disponibles

#### A. Crear Pregunta con Imágenes
```http
POST /api/questions
Content-Type: multipart/form-data

{
    "titulo": "Mi pregunta con imágenes",
    "contenido": "Descripción básica",
    "contenido_markdown": "## Mi pregunta\n\n![Imagen 1](file:0)\n\nTexto después de la imagen\n\n![Imagen 2](file:1)",
    "category_id": 1,
    "tags": [1, 2],
    "attachments": [imagen1.jpg, imagen2.png]
}
```

#### B. Subir Imagen Individual
```http
POST /api/images/upload
Content-Type: multipart/form-data

{
    "image": imagen.jpg,
    "question_id": 123 (opcional)
}
```

**Respuesta:**
```json
{
    "message": "Imagen subida exitosamente.",
    "attachment": {...},
    "url": "http://localhost:8080/storage/uploads/images/imagen_123456.jpg",
    "markdown": "![Imagen](http://localhost:8080/storage/uploads/images/imagen_123456.jpg)"
}
```

#### C. Subir Múltiples Imágenes
```http
POST /api/images/upload-multiple
Content-Type: multipart/form-data

{
    "images": [imagen1.jpg, imagen2.png, imagen3.gif],
    "question_id": 123 (opcional)
}
```

**Respuesta:**
```json
{
    "message": "Imágenes subidas exitosamente.",
    "images": [
        {
            "index": 0,
            "attachment": {...},
            "url": "http://localhost:8080/storage/uploads/images/imagen1_123456.jpg",
            "markdown": "![Imagen 0](http://localhost:8080/storage/uploads/images/imagen1_123456.jpg)",
            "placeholder": "![Imagen 0](file:0)"
        },
        ...
    ],
    "count": 3
}
```

### 3. Manejo de Placeholders

#### A. En el Contenido Markdown
El sistema procesa automáticamente estos placeholders:

```markdown
# Mi pregunta con imágenes

![Descripción de la imagen](file:0)
![Segunda imagen](placeholder:1)
![Tercera imagen](file:2)
```

#### B. Conversión Automática
- `![alt](file:0)` → `![alt](http://localhost:8080/storage/uploads/images/imagen_123.jpg)`
- `![alt](placeholder:1)` → `![alt](http://localhost:8080/storage/uploads/images/imagen_456.png)`

### 4. Ejemplo de Frontend (JavaScript/React)

#### A. Componente de Editor con Imágenes
```javascript
// Subir imágenes antes de enviar la pregunta
const uploadImages = async (files) => {
    const formData = new FormData();
    files.forEach(file => formData.append('images', file));
    
    const response = await fetch('/api/images/upload-multiple', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`,
        },
        body: formData
    });
    
    return await response.json();
};

// Crear pregunta con imágenes integradas
const createQuestion = async (questionData, images) => {
    // 1. Subir imágenes primero
    const uploadResult = await uploadImages(images);
    
    // 2. Reemplazar placeholders en markdown
    let markdown = questionData.contenido_markdown;
    uploadResult.images.forEach((img, index) => {
        markdown = markdown.replace(
            new RegExp(`!\\[([^\\]]*)\\]\\(file:${index}\\)`, 'g'),
            `![${img.attachment.original_name}](${img.url})`
        );
    });
    
    // 3. Crear FormData para la pregunta
    const formData = new FormData();
    formData.append('titulo', questionData.titulo);
    formData.append('contenido', questionData.contenido);
    formData.append('contenido_markdown', markdown);
    formData.append('category_id', questionData.category_id);
    
    // Agregar tags
    questionData.tags.forEach(tag => {
        formData.append('tags[]', tag);
    });
    
    // Agregar archivos adjuntos (imágenes)
    images.forEach(image => {
        formData.append('attachments[]', image);
    });
    
    // 4. Enviar pregunta
    const response = await fetch('/api/questions', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`,
        },
        body: formData
    });
    
    return await response.json();
};
```

#### B. Editor de Markdown con Preview
```javascript
const MarkdownEditor = ({ value, onChange, onImageUpload }) => {
    const handleImagePaste = async (e) => {
        const items = Array.from(e.clipboardData.items);
        const imageItems = items.filter(item => item.type.startsWith('image/'));
        
        if (imageItems.length > 0) {
            e.preventDefault();
            
            for (let item of imageItems) {
                const file = item.getAsFile();
                const uploadResult = await onImageUpload([file]);
                
                if (uploadResult.images && uploadResult.images.length > 0) {
                    const imageMarkdown = uploadResult.images[0].markdown;
                    onChange(value + '\n\n' + imageMarkdown);
                }
            }
        }
    };
    
    const handleFileDrop = async (e) => {
        e.preventDefault();
        const files = Array.from(e.dataTransfer.files);
        const imageFiles = files.filter(file => file.type.startsWith('image/'));
        
        if (imageFiles.length > 0) {
            const uploadResult = await onImageUpload(imageFiles);
            
            if (uploadResult.images) {
                const markdownImages = uploadResult.images
                    .map(img => img.markdown)
                    .join('\n\n');
                onChange(value + '\n\n' + markdownImages);
            }
        }
    };
    
    return (
        <textarea
            value={value}
            onChange={(e) => onChange(e.target.value)}
            onPaste={handleImagePaste}
            onDrop={handleFileDrop}
            onDragOver={(e) => e.preventDefault()}
            placeholder="Escribe tu pregunta en Markdown. Puedes pegar o arrastrar imágenes directamente."
        />
    );
};
```

### 5. Configuración de Almacenamiento

#### A. Estructura de Archivos
```
storage/app/public/uploads/
├── images/
│   ├── imagen_20250804_123456.jpg
│   ├── imagen_20250804_123457.png
│   └── ...
├── documents/
├── videos/
└── audios/
```

#### B. URLs Públicas
- **Desarrollo**: `http://localhost:8080/storage/uploads/images/archivo.jpg`
- **Producción**: `https://tu-dominio.com/storage/uploads/images/archivo.jpg`

### 6. Validaciones y Límites

#### A. Imágenes
- **Formatos**: JPG, JPEG, PNG, GIF, WEBP, SVG
- **Tamaño máximo**: 5MB por imagen
- **Dimensiones**: Se redimensiona automáticamente a máximo 1920x1080
- **Optimización**: Calidad 85% para JPG/PNG

#### B. Otros Archivos
- **Documentos**: PDF, DOC, DOCX, TXT, RTF, ODT
- **Videos**: MP4, AVI, MOV, WMV, FLV, WEBM
- **Audio**: MP3, WAV, OGG, M4A, AAC
- **Tamaño máximo**: 10MB por archivo

### 7. Seguridad

#### A. Validación de Archivos
- Verificación de MIME type
- Validación de extensión
- Sanitización automática de nombres
- Prevención de ejecución de scripts

#### B. Acceso Controlado
- Solo usuarios autenticados pueden subir archivos
- Los archivos se asocian al usuario que los sube
- Control de permisos para eliminar archivos

### 8. Ejemplos de Uso Práctico

#### A. Pregunta con Diagramas
```markdown
# ¿Cómo implementar este patrón de diseño?

Tengo la siguiente estructura:

![Diagrama actual](file:0)

Y quiero convertirla a:

![Diagrama objetivo](file:1)

¿Cuál sería la mejor forma de refactorizar el código?
```

#### B. Pregunta con Capturas de Pantalla
```markdown
# Error en mi aplicación React

Estoy obteniendo este error:

![Error en consola](file:0)

Mi código actual es:

![Código problemático](file:1)

¿Alguien sabe qué podría estar causando esto?
```

### 9. Testing

#### A. Test de Subida de Imágenes
```bash
# Test con curl
curl -X POST http://localhost:8080/api/images/upload \
  -H "Authorization: Bearer your-jwt-token" \
  -F "image=@test-image.jpg"
```

#### B. Test de Pregunta con Imágenes
```bash
curl -X POST http://localhost:8080/api/questions \
  -H "Authorization: Bearer your-jwt-token" \
  -F "titulo=Pregunta con imagen" \
  -F "contenido=Descripción básica" \
  -F "contenido_markdown=# Mi pregunta\n\n![Imagen](file:0)" \
  -F "category_id=1" \
  -F "tags[]=1" \
  -F "attachments[]=@imagen.jpg"
```

---

## 🚀 Próximas Mejoras

1. **Redimensionamiento dinámico** de imágenes
2. **Watermarks** automáticos
3. **Galería de imágenes** en las preguntas
4. **Compresión avanzada** de imágenes
5. **CDN integration** para mejor rendimiento
6. **Thumbnails** automáticos
7. **Lazy loading** de imágenes

---

Esta implementación proporciona una base sólida para el manejo de imágenes en el foro académico, permitiendo una experiencia de usuario rica y profesional.
