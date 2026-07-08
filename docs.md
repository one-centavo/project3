# Stack

## Frontend (Dispositivo Móvil / PWA)

- Blade Templates & TailwindCSS: Para estructurar y diseñar una interfaz limpia, adaptada a pantallas de celulares.

- Livewire: Componente encargado de la reactividad del sistema y la comunicación directa con el servidor mientras exista una conexión activa a internet.

- Alpine.js: Framework de JavaScript (integrado en Livewire) encargado de interceptar el formulario y controlar la interfaz de usuario cuando el dispositivo se quede sin señal.

- Service Workers: Scripts en segundo plano que permiten la instalación de la PWA en Android y aseguran que la aplicación visual cargue incluso sin internet.

- IndexedDB: Base de datos local e interna del navegador del celular donde Alpine.js guardará temporalmente los registros en formato JSON con el flag is_synced = false.

## Backend (Servidor en la Nube)

- Laravel Framework (PHP): Motor central encargado de recibir las peticiones de datos, procesar la lógica de negocio y ejecutar las validaciones de seguridad.

- Eloquent ORM: Herramientas de abstracción de datos de Laravel para interactuar con la base de datos de manera limpia y estructurada.

- Mecanismo de Sincronización (Ruta / API REST): Un endpoint dedicado (POST /api/sincronizar-clientes) que recibirá los lotes de datos JSON desde el celular cuando se restablezca la conexión.

- Base de Datos Central (MySQL): Motor relacional centralizado donde se consolidará de manera definitiva y permanente la información de los clientes del gimnasio.

```mermaid
graph TD
    inicio(Inicio) --> A{¿Hay conexión a internet?}

    %% CAMINO OFFLINE
    A -->|No| C[Crear registro de forma local]
    C --> C1[Guardar en IndexedDB]
    C1 --> C2{¿Se restableció la conexión?}
    C2 -->|No| C2
    C2 -->|Sí| B

    %% CAMINO ONLINE / SINCRONIZACIÓN
    A -->|Sí| B{¿Existe el UUID en la base de datos central?}

    %% SI EL UUID YA EXISTE (Es una modificación de un registro existente)
    B -->|Sí| D{¿El updated_at entrante es más reciente?}
    D -->|Sí| D_SI[Actualizar registro en MySQL con los nuevos datos]
    D -->|No| D_NO[Descartar cambios entrantes y mantener los del servidor]

    %% SI EL UUID NO EXISTE (Es una creación de un registro nuevo)
    B -->|No| validationDni{¿El DNI/Correo ya está registrado con otro UUID?}

    %% NUEVA LÓGICA SOLICITADA PARA EL CAMINO "SÍ" DEL DNI
    validationDni -->|Sí| checkChanges{¿Los datos entrantes son diferentes al registro guardado?}
    checkChanges -->|No| D_NO
    checkChanges -->|Sí| checkTime{¿El updated_at entrante es más reciente?}
    checkTime -->|Sí| D_SI
    checkTime -->|No| D_NO

    validationDni -->|No| F[Guardar registro nuevo en MySQL]

    %% CIERRE DEL PROCESO
    D_SI --> fin_sync[Cambiar flag local a is_sync = TRUE]
    F --> fin_sync
    D_NO --> fin(Fin)
    fin_sync --> fin
```
