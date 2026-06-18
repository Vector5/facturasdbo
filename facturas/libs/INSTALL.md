# Guia de Instalacion - Sistema de Facturacion

## Requisitos Previos

- Hosting en Hostinger con PHP 8.0 o superior
- Acceso a terminal SSH o Terminal de Hostinger
- Base de datos MySQL
- Acceso a phpMyAdmin

---

## Paso 1: Subir los Archivos

1. Comprima la carpeta `facturas/` en un archivo ZIP.
2. Acceda al **Administrador de Archivos** de Hostinger.
3. Suba el archivo ZIP al directorio `public_html/` (o el directorio raiz de su dominio).
4. Extraiga el ZIP en el servidor.

---

## Paso 2: Importar la Base de Datos

1. Acceda a **phpMyAdmin** desde el panel de Hostinger.
2. Seleccione su base de datos (o cree una nueva).
3. Haga clic en la pestana **Importar**.
4. Seleccione el archivo `database.sql` ubicado en la raiz del proyecto.
5. Haga clic en **Ejecutar** para importar las tablas.

---

## Paso 3: Configurar la Base de Datos

1. Abra el archivo `config/database.php`.
2. Modifique las credenciales con los datos de su base de datos en Hostinger:

```php
private static string $host = 'localhost';
private static string $dbName = 'su_base_de_datos';
private static string $username = 'su_usuario';
private static string $password = 'su_contraseña';
```

---

## Paso 4: Instalar DomPDF (Generacion de PDF)

DomPDF es necesario para generar los comprobantes en formato PDF.

### Opcion A: Via Terminal SSH de Hostinger

1. Acceda a **Avanzado > Terminal SSH** en su panel de Hostinger.
2. Navegue al directorio del proyecto:

```bash
cd public_html/facturas
```

3. Si Composer no esta instalado, descargarlo:

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```

4. Instale DomPDF:

```bash
php composer.phar require dompdf/dompdf
```

### Opcion B: Subir manualmente

1. En su computadora local, cree una carpeta temporal y ejecute:

```bash
composer require dompdf/dompdf
```

2. Suba la carpeta `vendor/` generada al directorio `facturas/` en su servidor.

---

## Paso 5: Instalar PHPMailer (Envio de Correos)

PHPMailer es necesario para enviar las facturas por correo electronico.

### Via Terminal SSH:

```bash
cd public_html/facturas
php composer.phar require phpmailer/phpmailer
```

### O si ya instalo DomPDF con Composer:

```bash
cd public_html/facturas
php composer.phar require phpmailer/phpmailer
```

Si subio `vendor/` manualmente, agregue PHPMailer de la misma forma:

```bash
composer require phpmailer/phpmailer
```

Y vuelva a subir la carpeta `vendor/` actualizada.

---

## Paso 6: Configurar SMTP (Envio de Correos)

Abra el archivo `config/app.php` y configure las constantes SMTP:

### Para correo de Hostinger:

```php
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'tu-correo@tudominio.com');
define('SMTP_PASS', 'tu-contraseña-de-correo');
define('SMTP_FROM_EMAIL', 'tu-correo@tudominio.com');
define('SMTP_FROM_NAME', 'Bodega de Almacenes');
```

### Para Gmail (alternativa):

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'tu-correo@gmail.com');
define('SMTP_PASS', 'tu-contraseña-de-aplicacion');
define('SMTP_FROM_EMAIL', 'tu-correo@gmail.com');
define('SMTP_FROM_NAME', 'Bodega de Almacenes');
```

**Nota para Gmail:** Debe generar una "Contrasena de aplicacion" en la configuracion
de seguridad de su cuenta de Google. No use su contrasena normal.

---

## Paso 7: Configurar Permisos de Directorios

El sistema necesita permisos de escritura en el directorio de PDFs generados.

### Via Terminal SSH:

```bash
cd public_html/facturas
chmod 755 uploads/
chmod 755 uploads/pdf/
```

### Via Administrador de Archivos:

1. Navegue a `facturas/uploads/pdf/`
2. Haga clic derecho en la carpeta `pdf`
3. Seleccione **Permisos**
4. Establezca el valor a **755**
5. Repita para la carpeta `uploads`

---

## Paso 8: Configurar la URL de la Aplicacion

Abra `config/app.php` y actualice la URL base:

```php
define('APP_URL', 'https://su-dominio.com/facturas');
```

---

## Paso 9: Configurar el PIN de Acceso

El sistema utiliza un PIN para autenticacion. Asegurese de que la tabla `configuracion`
en la base de datos tenga el PIN configurado (se crea al importar `database.sql`).

---

## Verificacion

Una vez completados todos los pasos:

1. Acceda a `https://su-dominio.com/facturas/` en su navegador.
2. Ingrese el PIN para acceder al panel.
3. Cree una factura de prueba.
4. Verifique que el PDF se genera correctamente.
5. Verifique que el correo se envia (si configuro SMTP).

---

## Solucion de Problemas

### El PDF no se genera
- Verifique que DomPDF esta instalado: debe existir `vendor/dompdf/`
- Verifique permisos de `uploads/pdf/` (755)
- Revise los logs de PHP en Hostinger

### El correo no se envia
- Verifique que PHPMailer esta instalado: debe existir `vendor/phpmailer/`
- Confirme las credenciales SMTP en `config/app.php`
- En Hostinger, asegurese de que el puerto SMTP no esta bloqueado
- Para Gmail, use una "Contrasena de aplicacion"

### Error de conexion a base de datos
- Verifique las credenciales en `config/database.php`
- Confirme que la base de datos existe en phpMyAdmin
- El host normalmente es `localhost` en Hostinger

### Error 500 Internal Server Error
- Verifique que PHP 8.0+ esta activo en su plan de Hostinger
- Revise el archivo `.htaccess` este correctamente subido
- Consulte los logs de errores en el panel de Hostinger
