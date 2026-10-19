# Compatibilidad de PHP y el editor

El entorno ejecuta PHP 8.4. El código se formatea con sintaxis PHP 8.2 para que también lo comprenda el analizador PHP IntelliSense instalado en VS Code.

Ejemplo compatible:

```php
$result = (new ProductService($database))->find($id);
```

El formato `new ProductService($database)->find($id)` pertenece a PHP 8.4 y originaba errores de análisis en la extensión antigua. No se desactivaron diagnósticos: se conservaron los paréntesis y se cambió `phpVersion` en `.prettierrc.json` para evitar que vuelvan a desaparecer.

La revisión local pasó de 224 avisos sintácticos a cero con el mismo parser de la extensión. PHP, Composer y las plantillas también se comprueban por separado. Si VS Code conserva marcas antiguas, guarda los archivos o ejecuta «Developer: Reload Window» para refrescar su análisis.
