<?php

namespace Database\Seeders;

use App\Models\Nota;
use Illuminate\Database\Seeder;

/**
 * Las cuatro notas que hoy están publicadas en «Actualízate con Nosotros».
 *
 * El texto es el de ellos, traído de su sitio tal cual: el cliente pidió que la
 * página nueva se pareciera a la que sus clientes ya conocen, y eso incluye lo
 * que dice. Sólo se corrigieron dos cosas que eran defectos, no estilo:
 *
 *   · su CMS dejó frases pegadas sin espacio («…dolor de cabeza.La ley que…»),
 *     y aquí van como párrafos aparte, con las mismas palabras;
 *   · los resúmenes de las tarjetas estaban cortados a media palabra
 *     («…y amigos, debemo»), y aquí terminan la frase.
 *
 * `updateOrCreate` por slug: estas cuatro se refrescan a propósito, para poder
 * recargar el texto original si alguien lo estropea editando.
 */
class NotasSeeder extends Seeder
{
    public function run(): void
    {
        $notas = [
            [
                'titulo' => 'Mitos y verdades de la revisión Técnico mecánica',
                'slug' => 'mitos-y-verdades-de-la-revision-tecnicomecanica',
                'categoria' => 'Noticias',
                'imagen' => '/img/notas/nota-revision-tecnicomecanica-1024.webp',
                'publicada_en' => '2023-05-25 15:14:17',
                'resumen' => 'Aunque muchas veces parece un dolor de cabeza, la revisión técnico-mecánica es un deber que tenemos como ciudadanos. Te dejamos algunos tips para que consigas el certificado sin contratiempos.',
                'cuerpo' => <<<'TEXTO'
Aunque muchas veces parece un dolor de cabeza, debemos ser conscientes que la revisión Técnico mecánica es un deber que tenemos como ciudadanos y por esta razón se convierte en un compromiso tanto para evitar cualquier accidente por fallas mecánicas, como cualquier multa que pueda acarrear el incumplimiento de las normas.
La ley que reglamenta esta norma dice:
La revisión se debe hacer al sexto año (6°) contado a partir de la fecha de matrícula. Es por esto que a continuación te enumeraremos algunos tips para que no tengas dolores de cabeza tratando de conseguir el certificado.
La primera recomendación que dan algunos expertos es hacer una revisión general, es decir: verificar amortiguadores, espirales, brazos axiales, terminales de dirección, bujes de suspensión, muñecos, (bieletas), guardapolvos de amortiguadores entre otros.
## Accesorios
Se deben verificar los accesorios, que no estén sueltos y algo que muchas veces olvidamos pero es muy importante son las plumillas limpiaparabrisas, estos deben estar en un buen estado, con el fin de evitar rayones en el vidrio.
## El Sistema de Frenos
Es importante verificar que los frenos del vehículo estén óptimamente calibrados, no deben hacer ruido al frenar ya que esto puede implicar un desgaste en las pastilla, el freno de mano debe bloquear el vehículo totalmente.
## Emisión de gases
Algunos factores que pueden generar mayor cantidad de gases contaminantes son: daños en el sensor de oxígeno, sincronización no adecuada, (Bujías, cables de alta, filtro de aire).
Es importante verificar el estado de nuestro vehículo con el fin de recibir el certificado sin ningún contratiempo.
Un dato para no olvidar es que si no se pasa la revisión Técnico mecánica se tiene un plazo de 15 días para reparar las fallas que el vehículo hubiera podido presentar.
La mejor calidad en Repuestos la encuentras en Importadora Sur Alpine, donde podrás encontrar los mejores Repuestos para suspensión, frenos, lubricantes y mucho más. Adicionalmente contamos con un personal altamente capacitado. Comunícate al 3660066 o a los 3134223861 envíos a todo el país.
TEXTO,
            ],
            [
                'titulo' => 'Consejos de manejo preventivo en esta temporada de Vacaciones',
                'slug' => 'consejos-de-manejo-preventivo-en-vacaciones',
                'categoria' => 'Tips',
                'imagen' => '/img/notas/nota-manejo-preventivo-vacaciones-1024.webp',
                'publicada_en' => '2023-05-25 15:13:51',
                'resumen' => 'En esta temporada en la que queremos tomarnos un descanso y pasar fin de año en otra ciudad o municipio, queremos dejarte algunos consejos de manejo preventivo para la carretera.',
                'cuerpo' => <<<'TEXTO'
Lo primero que debemos tener en cuenta es verificar el estado de nuestro vehículo:
SISTEMA DE FRENOS, SUSPENSIÓN, MOTOR, LUBRICANTES, estado de ruedas entre otros.
A continuación encontraras un listado de algunos consejos que debemos tener en cuenta al momento de iniciar nuestro viaje:
Mantener limpio el parabrisas y los espejos con el fin de mejorar la visibilidad.
Antes de iniciar el viaje verificar que tienes la visibilidad correcta, es decir, si el asiento está en la mejor posición tanto de visibilidad hacia los espejos como la parte delantera.
Restringir cualquier distracción dentro del vehículo.
Siempre estar alerta en la carretera.
Mantener una distancia adecuada entre tu vehículo y el que va adelante con el fin de tener suficiente tiempo para reaccionar.
Respetar los límites de velocidad tanto en horas del día como de la noche. Un gran número de accidentes que suceden en horas de la noche es por el exceso de velocidad.
Si estás estresado, cansado o mareado es mejor tomar un descanso.
Mantener los limpiaparabrisas en perfecto estado.
Lo más importante en estas fechas es llegar bien a nuestro destino, por esta razón la mejor manera de prevenir un accidente es estar atentos en la carretera, planear nuestro viaje y verificar el estado de nuestro vehículo.
Por este motivo en Importadora sur Alpine, tenemos la mejor calidad en Repuestos para su vehículo. Servicio a domicilio y envíos a todo el país al 3660066 o al 3134223861, donde nuestros asesores te atenderán
TEXTO,
            ],
            [
                'titulo' => '¿Que es el kit de distribución?',
                'slug' => 'que-es-el-kit-de-distribucion',
                'categoria' => 'De interés',
                'imagen' => '/img/notas/nota-kit-de-distribucion-1024.webp',
                'publicada_en' => '2023-05-25 15:12:00',
                'resumen' => 'El kit de distribución es un grupo de repuestos fundamentales en nuestro vehículo. Muchas veces desconocemos su funcionamiento y el momento correcto en el que se debe hacer el cambio preventivo.',
                'cuerpo' => <<<'TEXTO'
El Kit de Distribución, es un grupo de repuestos fundamentales en nuestro vehículo, muchas veces desconocemos su funcionamiento y el momento correcto en el que se debe hacer el cambio preventivo
Lo primero es conocer sus funciones, transmitir el movimiento del cigüeñal al eje de levas encargado de abrir y cerrar las válvulas; así permitir la mezcla de aire con el combustible; la bujía se encarga de producir la chispa y de esta manera generar el movimiento del motor.
Por ello debemos estar muy pendientes de su vida útil. El cambio debe hacerse según las especificaciones en el manual de fabricante, puesto que para algunos vehículos se deben hacer el cambio de otros repuestos, también se debe tener en cuenta la opinión de varios expertos; que sugieren realizar el cambio algunos kilómetros antes del estipulado por el fabricante, esto para aquellos vehículos que tienen sus recorridos en zonas urbanas puesto que no es lo mismo recorrer 200 Km. en una zona donde debe detenerse cada tres cuadras, sea por un semáforo, trancón, huecos, etc. Comparándolo con aquellos vehículos que recorren el mismo kilometraje a una velocidad constante y sin detenerse mucho.
Aunque es muy difícil prever si el kit de distribución de nuestro vehículo ha cumplido su ciclo, podemos verificar el kilometraje y hacer el cambio de los repuestos. Si no conocemos el kilometraje de nuestro vehículo algunos expertos recomiendan hacerlo cada 5 año, para evitar dolores de cabeza.
Cuando compramos un vehículo usado lo recomendado es verificar el estado del kit, si no estamos seguros de su estado lo más recomendable es cambiarlo y empezar a llevar las cuentas para el próximo cambio.
Lo barato puede salir caro, por eso lo mejor es solicitar los repuestos de la mejor calidad, no escatimar en gastos puesto que más adelante nos puede generar grandes inconvenientes.
En Importadora Sur Alpine es muy importante la calidad y el correcto asesoramiento a nuestros clientes, con un personal altamente calificado. Comprendemos que un vehículo es importante en el hogar, nos lleva y trae en familia, por eso nos comprometemos en ofrecer la mejor calidad en repuestos.
Nuestros clientes tienen la certeza de adquirir repuestos de la mejor calidad, depositando su confianza en una compañía con más de treinta y cinco años de experiencia en el mercado.
Comunícate al teléfono 3660066 o al 3134223861, servicio a domicilio y envíos a todo el país
TEXTO,
            ],
            [
                'titulo' => 'Como debo preparar mi carro antes de salir de Vacaciones',
                'slug' => 'como-preparar-mi-carro-antes-de-salir-de-vacaciones',
                'categoria' => 'De interés',
                'imagen' => '/img/notas/nota-preparar-carro-vacaciones-1024.webp',
                'publicada_en' => '2023-05-25 15:11:24',
                'resumen' => 'En esta semana de receso, en la que queremos descansar y compartir tiempo con familiares y amigos, debemos tomar algunas precauciones antes de salir a carretera.',
                'cuerpo' => <<<'TEXTO'
En esta semana de receso en la cual queremos tener un descanso y compartir tiempo con familiares y amigos, debemos tomar algunas precauciones.
Previo a iniciar nuestro viaje es importante revisar algunos aspectos de nuestro vehículo:
- Verificar los Niveles: Es muy importante comprobar los niveles de aceite y Líquido de frenos
- Sistema de Frenos: Verificar el buen estado de las pastillas y el disco
- Sistema de refrigeración: Es importante revisar el nivel del líquido Refrigerante, las mangueras que lo transportan y el radiador, esto es clave para que el vehículo no se recaliente.
- Sistema de distribución: Se recomienda rectificar el kilometraje desde el ultimo cambio del sistema de Repartición según el catalogo de su vehículo.
- Plumillas Limpiabrisas: Revisar el buen estado. Si no funcionan de una manera correcta, puede ser un riesgo para la integridad de los ocupantes del vehículo, ya que en una lluvia intensa dificulta la visibilidad del conductor.
Es por eso que en Importadora Sur Alpine, ofrecemos la mejor línea de Repuestos para su vehículo, comunícate al teléfono 3660066 o al 3134223861, Servicio a Domicilio y Envíos a todo el país.
También debemos recordar que no solamente debemos tener en cuenta el estado del vehículo, es importante tener en cuenta otros factores:
- DOCUMENTACIÓN
- Verificar la documentación del vehículo y la del conductor, muchas veces con el día a día olvidamos la fecha de vencimiento.
- No olvides que los documentos son:
- Tarjeta de propiedad del vehículo
- SOAT
- Revisión Técnico mecánica
- Documento de Identificación (Conductor y todos los ocupantes)
- Licencia de Conducción
- Organización del viaje
- Asegurarnos que todos tengan el cinturón de seguridad.
- Si llevas niños menores de 12 años siempre llevarlos en la parte trasera del vehículo
- Respetar las normas de transito.
TEXTO,
            ],
        ];

        foreach ($notas as $nota) {
            Nota::updateOrCreate(['slug' => $nota['slug']], $nota + ['publicada' => true]);
        }
    }
}
