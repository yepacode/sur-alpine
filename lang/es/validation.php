<?php

/**
 * Los mensajes de validación, en español.
 *
 * Faltaba este archivo entero. `APP_LOCALE=es` sin `lang/es/validation.php`
 * hace que Laravel caiga al inglés del framework, así que sólo se veían en
 * español los mensajes escritos a mano controlador por controlador. El
 * resultado eran listas bilingües absurdas —«The placa field is required.»
 * encima de «Escribe qué se le hizo al carro.»— y, peor, un inglés seco en el
 * momento exacto de la conversión: al enviar la cotización.
 *
 * Para un cliente cuyo problema es que lo suplantan, un error en inglés es la
 * señal más barata de «esta no es la página buena».
 *
 * El tuteo es deliberado: es como habla el resto del sitio y como se habla en
 * el mostrador.
 */
return [

    'accepted' => 'Tienes que aceptar :attribute.',
    'active_url' => ':Attribute no es una dirección web válida.',
    'after' => ':Attribute tiene que ser posterior al :date.',
    'after_or_equal' => ':Attribute no puede ser anterior al :date.',
    'alpha' => ':Attribute sólo puede tener letras.',
    'alpha_dash' => ':Attribute sólo puede tener letras, números, guiones y guiones bajos.',
    'alpha_num' => ':Attribute sólo puede tener letras y números.',
    'array' => ':Attribute tiene que ser una lista.',
    'before' => ':Attribute tiene que ser anterior al :date.',
    'before_or_equal' => ':Attribute no puede ser posterior al :date.',
    'between' => [
        'array' => ':Attribute tiene que tener entre :min y :max elementos.',
        'file' => ':Attribute tiene que pesar entre :min y :max kilobytes.',
        'numeric' => ':Attribute tiene que estar entre :min y :max.',
        'string' => ':Attribute tiene que tener entre :min y :max caracteres.',
    ],
    'boolean' => ':Attribute sólo puede ser sí o no.',
    'confirmed' => ':Attribute no coincide con la confirmación.',
    'current_password' => 'La contraseña no es correcta.',
    'date' => ':Attribute no es una fecha válida.',
    'date_equals' => ':Attribute tiene que ser el :date.',
    'date_format' => ':Attribute no tiene el formato :format.',
    'declined' => 'Tienes que rechazar :attribute.',
    'different' => ':Attribute y :other tienen que ser distintos.',
    'digits' => ':Attribute tiene que tener :digits dígitos.',
    'digits_between' => ':Attribute tiene que tener entre :min y :max dígitos.',
    'dimensions' => ':Attribute no tiene un tamaño de imagen válido.',
    'distinct' => ':Attribute está repetido.',
    'email' => ':Attribute tiene que ser un correo válido.',
    'ends_with' => ':Attribute tiene que terminar en: :values.',
    'exists' => ':Attribute no existe.',
    'file' => ':Attribute tiene que ser un archivo.',
    'filled' => ':Attribute no puede quedar vacío.',
    'gt' => [
        'array' => ':Attribute tiene que tener más de :value elementos.',
        'file' => ':Attribute tiene que pesar más de :value kilobytes.',
        'numeric' => ':Attribute tiene que ser mayor que :value.',
        'string' => ':Attribute tiene que tener más de :value caracteres.',
    ],
    'gte' => [
        'array' => ':Attribute tiene que tener :value elementos o más.',
        'file' => ':Attribute tiene que pesar :value kilobytes o más.',
        'numeric' => ':Attribute tiene que ser :value o más.',
        'string' => ':Attribute tiene que tener :value caracteres o más.',
    ],
    'image' => ':Attribute tiene que ser una imagen.',
    'in' => ':Attribute no es una de las opciones válidas.',
    'in_array' => ':Attribute no está entre las opciones de :other.',
    'integer' => ':Attribute tiene que ser un número entero.',
    'ip' => ':Attribute tiene que ser una dirección IP válida.',
    'json' => ':Attribute tiene que ser un texto JSON válido.',
    'lowercase' => ':Attribute tiene que ir en minúsculas.',
    'lt' => [
        'array' => ':Attribute tiene que tener menos de :value elementos.',
        'file' => ':Attribute tiene que pesar menos de :value kilobytes.',
        'numeric' => ':Attribute tiene que ser menor que :value.',
        'string' => ':Attribute tiene que tener menos de :value caracteres.',
    ],
    'lte' => [
        'array' => ':Attribute no puede tener más de :value elementos.',
        'file' => ':Attribute no puede pesar más de :value kilobytes.',
        'numeric' => ':Attribute no puede ser mayor que :value.',
        'string' => ':Attribute no puede tener más de :value caracteres.',
    ],
    'max' => [
        'array' => ':Attribute no puede tener más de :max elementos.',
        'file' => ':Attribute no puede pesar más de :max kilobytes.',
        'numeric' => ':Attribute no puede ser mayor que :max.',
        'string' => ':Attribute no puede tener más de :max caracteres.',
    ],
    'mimes' => ':Attribute tiene que ser un archivo de tipo: :values.',
    'mimetypes' => ':Attribute tiene que ser un archivo de tipo: :values.',
    'min' => [
        'array' => ':Attribute tiene que tener al menos :min elementos.',
        'file' => ':Attribute tiene que pesar al menos :min kilobytes.',
        'numeric' => ':Attribute tiene que ser :min o más.',
        'string' => ':Attribute tiene que tener al menos :min caracteres.',
    ],
    'not_in' => ':Attribute no es una de las opciones válidas.',
    'not_regex' => ':Attribute no tiene un formato válido.',
    'numeric' => ':Attribute tiene que ser un número.',
    'present' => 'Falta :attribute.',
    'prohibited' => ':Attribute no se puede enviar.',
    'regex' => ':Attribute no tiene el formato esperado.',
    'required' => 'Falta :attribute.',
    'required_if' => 'Falta :attribute cuando :other es :value.',
    'required_unless' => 'Falta :attribute.',
    'required_with' => 'Falta :attribute cuando se envía :values.',
    'required_without' => 'Falta :attribute cuando no se envía :values.',
    'same' => ':Attribute y :other tienen que coincidir.',
    'size' => [
        'array' => ':Attribute tiene que tener :size elementos.',
        'file' => ':Attribute tiene que pesar :size kilobytes.',
        'numeric' => ':Attribute tiene que ser :size.',
        'string' => ':Attribute tiene que tener :size caracteres.',
    ],
    'starts_with' => ':Attribute tiene que empezar por: :values.',
    'string' => ':Attribute tiene que ser texto.',
    'unique' => ':Attribute ya está registrado.',
    'uploaded' => 'No pudimos subir :attribute. Puede que pese demasiado.',
    'uppercase' => ':Attribute tiene que ir en mayúsculas.',
    'url' => ':Attribute tiene que ser una dirección web válida.',

    'custom' => [],

    /**
     * Cómo se nombra cada campo en el mensaje.
     *
     * Con artículo, para que la frase salga entera: «Falta el teléfono», no
     * «Falta telefono». Y con el rótulo que la persona ve en pantalla, no con
     * el nombre interno: quien llena el formulario nunca vio la palabra
     * `periodicidad_valor`.
     */
    'attributes' => [
        'acepta' => 'la autorización de datos',
        'alias' => 'el alias del vehículo',
        'alt' => 'la descripción de la imagen',
        'anio_fin' => 'el año final',
        'anio_inicio' => 'el año inicial',
        'apellidos' => 'los apellidos',
        'archivo' => 'el archivo',
        'cantidad' => 'la cantidad',
        'cilindraje' => 'el cilindraje',
        'clave' => 'la contraseña',
        'confirmo' => 'la confirmación',
        'correo' => 'el correo',
        'correos_cotizacion' => 'los correos donde llegan las cotizaciones',
        'cuerpo' => 'el contenido',
        'descripcion' => 'la descripción',
        'direccion' => 'la dirección',
        'email' => 'el correo',
        'fecha' => 'la fecha',
        'horario_sabado' => 'el horario del sábado',
        'horario_semana' => 'el horario de lunes a viernes',
        'imagen' => 'la imagen',
        'kilometraje' => 'el kilometraje',
        'marca_id' => 'la marca',
        'mensaje' => 'el mensaje',
        'modelo_id' => 'el modelo',
        'name' => 'el nombre',
        'nombre' => 'el nombre',
        'notas' => 'los comentarios',
        'orden' => 'el orden',
        'password' => 'la contraseña',
        'password_actual' => 'la contraseña actual',
        'periodicidad_tipo' => 'cada cuánto avisamos',
        'periodicidad_valor' => 'cada cuánto avisamos',
        'placa' => 'la placa',
        'publicada_en' => 'la fecha de publicación',
        'referencia' => 'la referencia',
        'resumen' => 'el resumen',
        'rol' => 'el rol',
        'slug' => 'la dirección de la página',
        'telefono' => 'el teléfono',
        'telefono_pbx' => 'el PBX',
        'tipo' => 'el tipo',
        'titulo' => 'el título',
        'valor' => 'el valor',
        'whatsapp' => 'el WhatsApp',
    ],

];
