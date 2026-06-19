Especificación de requisitos de software

Proyecto: [Nombre del proyecto]
Revisión [99.99]

[Mes de año]

Instrucciones para el uso de este formato
Este formato es una plantilla tipo para documentos de requisitos del software.

Está basado y es conforme con el estándar IEEE Std 830-1998.

Las secciones que no se consideren aplicables al sistema descrito podrán de forma justificada indicarse como no aplicables (NA).

Notas:
Los textos en color azul son indicaciones que deben eliminarse y, en su caso, sustituirse por los contenidos descritos en cada apartado.

Los textos entre corchetes del tipo “[Inserte aquí el texto]” permiten la inclusión directa de texto con el color y estilo adecuado a la sección, al pulsar sobre ellos con el puntero del ratón.

Los títulos y subtítulos de cada apartado están definidos como estilos de MS Word, de forma que su numeración consecutiva se genera automáticamente según se trate de estilos “Titulo1, Titulo2 y Titulo3”.

La sangría de los textos dentro de cada apartado se genera automáticamente al pulsar Intro al final de la línea de título. (Estilos Normal indentado1, Normal indentado 2 y Normal indentado 3).

El índice del documento es una tabla de contenido que MS Word actualiza tomando como criterio los títulos del documento.
Una vez terminada su redacción debe indicarse a Word que actualice todo su contenido para reflejar el contenido definitivo.

De la plantilla de formato del documento © & Coloriuris http://www.qualitatis.org
.
Ficha del documento

Fecha Revisión Autor Verificado dep. calidad.
[Fecha]
[Rev]
[Descripcion]
[Firma o sello]

Documento validado por las partes en fecha: [Fecha]

Por el cliente Por la empresa suministradora

Fdo. D./ Dña [Nombre]
Fdo. D./Dña [Nombre]

Contenido
FICHA DEL DOCUMENTO 3
CONTENIDO 4
1 INTRODUCCIÓN 6
1.1 Propósito 6
1.2 Alcance 6
1.3 Personal involucrado 6
1.4 Definiciones, acrónimos y abreviaturas 6
1.5 Referencias 6
1.6 Resumen 6
2 DESCRIPCIÓN GENERAL 7
2.1 Perspectiva del producto 7
2.2 Funcionalidad del producto 7
2.3 Características de los usuarios 7
2.4 Restricciones 7
2.5 Suposiciones y dependencias 7
2.6 Evolución previsible del sistema 7
3 REQUISITOS ESPECÍFICOS 7
3.1 Requisitos comunes de los interfaces 8
3.1.1 Interfaces de usuario 8
3.1.2 Interfaces de hardware 8
3.1.3 Interfaces de software 8
3.1.4 Interfaces de comunicación 8
3.2 Requisitos funcionales 8
3.2.1 Requisito funcional 1 9
3.2.2 Requisito funcional 2 9
3.2.3 Requisito funcional 3 9
3.2.4 Requisito funcional n 9
3.3 Requisitos no funcionales 9
3.3.1 Requisitos de rendimiento 9
3.3.2 Seguridad 9
3.3.3 Fiabilidad 9
3.3.4 Disponibilidad 9
3.3.5 Mantenibilidad 10
3.3.6 Portabilidad 10
3.4 Otros requisitos 10
4 APÉNDICES 10

1 Introducción
[Inserte aquí el texto]
La introducción de la Especificación de requisitos de software (SRS) debe proporcionar una vista general de la SRS. Debe incluir el objetivo, el alcance, las definiciones y acrónimos, las referencias, y la vista general del SRS.
1.1 Propósito
[Inserte aquí el texto]
 Propósito del documento
 Audiencia a la que va dirigido
1.2 Alcance
[Inserte aquí el texto]
 Identificación del producto(s) a desarrollar mediante un nombre
 Consistencia con definiciones similares de documentos de mayor nivel (ej. Descripción del sistema) que puedan existir
1.3 Personal involucrado
Nombre [Inserte aquí el texto]

Rol [Inserte aquí el texto]

Categoría profesional [Inserte aquí el texto]

Responsabilidades [Inserte aquí el texto]

Información de contacto [Inserte aquí el texto]

Aprobación [Inserte aquí el texto]

Relación de personas involucradas en el desarrollo del sistema, con información de contacto.
Esta información es útil para que el gestor del proyecto pueda localizar a todos los participantes y recabar la información necesaria para la obtención de requisitos, validaciones de seguimiento, etc.
1.4 Definiciones, acrónimos y abreviaturas
[Inserte aquí el texto]
Definición de todos los términos, abreviaturas y acrónimos necesarios para interpretar apropiadamente este documento. En ella se pueden indicar referencias a uno o más apéndices, o a otros documentos.
1.5 Referencias
Referencia Titulo Ruta Fecha Autor
[Ref.]
[Título]
[Ruta]
[Fecha]
[Autor]

Relación completa de todos los documentos relacionados en la especificación de requisitos de software, identificando de cada documento el titulo, referencia (si procede), fecha y organización que lo proporciona.
1.6 Resumen
[Inserte aquí el texto]
 Descripción del contenido del resto del documento
 Explicación de la organización del documento
2 Descripción general
2.1 Perspectiva del producto
[Inserte aquí el texto]
Indicar si es un producto independiente o parte de un sistema mayor. En el caso de tratarse de un producto que forma parte de un sistema mayor, un diagrama que sitúe el producto dentro del sistema e identifique sus conexiones facilita la comprensión.
2.2 Funcionalidad del producto
[Inserte aquí el texto]
Resumen de las funcionalidades principales que el producto debe realizar, sin entrar en información de detalle.
En ocasiones la información de esta sección puede tomarse de un documento de especificación del sistema de mayor nivel (ej. Requisitos del sistema).
Las funcionalidades deben estar organizadas de manera que el cliente o cualquier interlocutor pueda entenderlo perfectamente. Para ello se pueden utilizar métodos textuales o gráficos.
2.3 Características de los usuarios
Tipo de usuario [Inserte aquí el texto]

Formación [Inserte aquí el texto]

Habilidades [Inserte aquí el texto]

Actividades [Inserte aquí el texto]

Descripción de los usuarios del producto, incluyendo nivel educacional, experiencia y experiencia técnica.
2.4 Restricciones
[Inserte aquí el texto]
Descripción de aquellas limitaciones a tener en cuenta a la hora de diseñar y desarrollar el sistema, tales como el empleo de determinadas metodologías de desarrollo, lenguajes de programación, normas particulares, restricciones de hardware, de sistema operativo etc.
2.5 Suposiciones y dependencias
[Inserte aquí el texto]
Descripción de aquellos factores que, si cambian, pueden afectar a los requisitos. Por ejemplo una asunción puede ser que determinado sistema operativo está disponible para el hardware requerido. De hecho, si el sistema operativo no estuviera disponible, la SRS debería modificarse.
2.6 Evolución previsible del sistema
[Inserte aquí el texto]
Identificación de futuras mejoras al sistema, que podrán analizarse e implementarse en un futuro.
3 Requisitos específicos
Esta es la sección más extensa y más importante del documento.
Debe contener una lista detallada y completa de los requisitos que debe cumplir el sistema a desarrollar. El nivel de detalle de los requisitos debe ser el suficiente para que el equipo de desarrollo pueda diseñar un sistema que satisfaga los requisitos y los encargados de las pruebas puedan determinar si éstos se satisfacen.

Los requisitos se dispondrán en forma de listas numeradas para su identificación, seguimiento, trazabilidad y validación (ej. RF 10, RF 10.1, RF 10.2,...).

Para cada requisito debe completarse la siguiente tabla:

Número de requisito [Inserte aquí el texto]

Nombre de requisito [Inserte aquí el texto]

Tipo 0 Requisito
0 Restricción

Fuente del requisito [Inserte aquí el texto]

Prioridad del requisito 0 Alta/Esencial
0 Media/Deseado
0 Baja/ Opcional

y realizar a continuación la descripción del requisito

La distribución de los párrafos que forman este punto puede diferir del propuesto en esta plantilla, si las características del sistema aconsejan otra distribución para ofrecer mayor claridad en la exposición.
3.1 Requisitos comunes de los interfaces
[Inserte aquí el texto]
Descripción detallada de todas las entradas y salidas del sistema de software.
3.1.1 Interfaces de usuario
[Inserte aquí el texto]
Describir los requisitos del interfaz de usuario para el producto. Esto puede estar en la forma de descripciones del texto o pantallas del interfaz. Por ejemplo posiblemente el cliente ha especificado el estilo y los colores del producto. Describa exacto cómo el producto aparecerá a su usuario previsto.
3.1.2 Interfaces de hardware
[Inserte aquí el texto]
Especificar las características lógicas para cada interfaz entre el producto y los componentes de hardware del sistema. Se incluirán características de configuración.
3.1.3 Interfaces de software
[Inserte aquí el texto]
Indicar si hay que integrar el producto con otros productos de software.
Para cada producto de software debe especificarse lo siguiente:
 Descripción del producto software utilizado
 Propósito del interfaz
 Definición del interfaz: contiendo y formato
3.1.4 Interfaces de comunicación
[Inserte aquí el texto]
Describir los requisitos del interfaces de comunicación si hay comunicaciones con otros sistemas y cuales son las protocolos de comunicación.
3.2 Requisitos funcionales
[Inserte aquí el texto]
Definición de acciones fundamentales que debe realizar el software al recibir información, procesarla y producir resultados.
En ellas se incluye:
 Comprobación de validez de las entradas
 Secuencia exacta de operaciones
 Respuesta a situaciones anormales (desbordamientos, comunicaciones, recuperación de errores)
 Parámetros
 Generación de salidas
 Relaciones entre entradas y salidas (secuencias de entradas y salidas, formulas para la conversión de información)
 Especificación de los requisitos lógicos para la información que será almacenada en base de datos (tipo de información, requerido)

Las requisitos funcionales pueden ser divididos en sub-secciones.
3.2.1 Requisito funcional 1
3.2.2 Requisito funcional 2
3.2.3 Requisito funcional 3
3.2.4 Requisito funcional n
3.3 Requisitos no funcionales
3.3.1 Requisitos de rendimiento
[Inserte aquí el texto]
Especificación de los requisitos relacionados con la carga que se espera tenga que soportar el sistema. Por ejemplo, el número de terminales, el número esperado de usuarios simultáneamente conectados, número de transacciones por segundo que deberá soportar el sistema, etc.
Todos estos requisitos deben ser mesurables. Por ejemplo, indicando “el 95% de las transacciones deben realizarse en menos de 1 segundo”, en lugar de “los operadores no deben esperar a que se complete la transacción”.
3.3.2 Seguridad
[Inserte aquí el texto]
Especificación de elementos que protegerán al software de accesos, usos y sabotajes maliciosos, así como de modificaciones o destrucciones maliciosas o accidentales. Los requisitos pueden especificar:
 Empleo de técnicas criptográficas.
 Registro de ficheros con “logs” de actividad.
 Asignación de determinadas funcionalidades a determinados módulos.
 Restricciones de comunicación entre determinados módulos.
 Comprobaciones de integridad de información crítica.
3.3.3 Fiabilidad
[Inserte aquí el texto]
Especificación de los factores de fiabilidad necesaria del sistema. Esto se expresa generalmente como el tiempo entre los incidentes permisibles, o el total de incidentes permisible.
3.3.4 Disponibilidad
[Inserte aquí el texto]
Especificación de los factores de disponibilidad final exigidos al sistema. Normalmente expresados en % de tiempo en los que el software tiene que mostrar disponibilidad.
3.3.5 Mantenibilidad
[Inserte aquí el texto]
Identificación del tipo de mantenimiento necesario del sistema.
Especificación de quien debe realizar las tareas de mantenimiento, por ejemplo usuarios, o un desarrollador.
Especificación de cuando debe realizarse las tareas de mantenimiento. Por ejemplo, generación de estadísticas de acceso semanales y mensuales.
3.3.6 Portabilidad
[Inserte aquí el texto]
Especificación de atributos que debe presentar el software para facilitar su traslado a otras plataformas u entornos. Pueden incluirse:
 Porcentaje de componentes dependientes del servidor.
 Porcentaje de código dependiente del servidor.
 Uso de un determinado lenguaje por su portabilidad.
 Uso de un determinado compilador o plataforma de desarrollo.
 Uso de un determinado sistema operativo.
3.4 Otros requisitos
[Inserte aquí el texto]
Cualquier otro requisito que no encaje en ninguna de las secciones anteriores.

Por ejemplo:
Requisitos culturales y políticos
Requisitos Legales
4 Apéndices
[Inserte aquí el texto]
Pueden contener todo tipo de información relevante para la SRS pero que, propiamente, no forme parte de la SRS.
