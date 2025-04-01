<?php
// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "diagnostico_organizacional";

// Conexión a la base de datos
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Procesar formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar si el email ya existe
    $email = $_POST['email'];
    $stmt = $conn->prepare("SELECT id FROM participantes WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Email existe, obtener el ID
        $row = $result->fetch_assoc();
        $participante_id = $row['id'];
    } else {
        // Email no existe, crear nuevo participante
        $stmt = $conn->prepare("INSERT INTO participantes (nombre, email, empresa) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $_POST['nombre'], $_POST['email'], $_POST['empresa']);
        $stmt->execute();
        $participante_id = $stmt->insert_id;
        $stmt->close();
    }
    
    // Recuperar respuestas del formulario
    $p1A = isset($_POST['p1A']) ? $_POST['p1A'] : null;
    $p1B = isset($_POST['p1B']) ? $_POST['p1B'] : null;
    $p2A = isset($_POST['p2A']) ? $_POST['p2A'] : null;
    $p2B = isset($_POST['p2B']) ? $_POST['p2B'] : null;
    $p3A = isset($_POST['p3A']) ? $_POST['p3A'] : null;
    $p3B = isset($_POST['p3B']) ? $_POST['p3B'] : null;
    $p4A = isset($_POST['p4A']) ? $_POST['p4A'] : null;
    $p4B = isset($_POST['p4B']) ? $_POST['p4B'] : null;
    $p5A = isset($_POST['p5A']) ? $_POST['p5A'] : null;
    $p5B = isset($_POST['p5B']) ? $_POST['p5B'] : null;
    $p6A = isset($_POST['p6A']) ? $_POST['p6A'] : null;
    $p6B = isset($_POST['p6B']) ? $_POST['p6B'] : null;
    $p7A = isset($_POST['p7A']) ? $_POST['p7A'] : null;
    $p7B = isset($_POST['p7B']) ? $_POST['p7B'] : null;

    // Verificar si alguna respuesta está en NULL
    if (is_null($p1A) || is_null($p1B) || is_null($p2A) || is_null($p2B) || is_null($p3A) || is_null($p3B) ||
        is_null($p4A) || is_null($p4B) || is_null($p5A) || is_null($p5B) || is_null($p6A) || is_null($p6B) ||
        is_null($p7A) || is_null($p7B)) {
        die("Error: Todas las preguntas deben ser respondidas.");
    }

    // Insertar respuestas
    $sql = "INSERT INTO respuestas (participante_id, p1A, p1B, p2A, p2B, p3A, p3B, p4A, p4B, p5A, p5B, p6A, p6B, p7A, p7B) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error en la preparación: " . $conn->error);
    }

    $stmt->bind_param("issssssssssssss", $participante_id, $p1A, $p1B, $p2A, $p2B, $p3A, $p3B, $p4A, $p4B, $p5A, $p5B, $p6A, $p6B, $p7A, $p7B);

    if (!$stmt->execute()) {
        die("Error en la ejecución: " . $stmt->error);
    }

    $respuesta_id = $stmt->insert_id;
    $stmt->close();
    
    // Calcular y guardar resultados
    $resultados = calcularResultados($conn, $participante_id);
    
    // Mostrar resultados
    mostrarResultados($resultados);
    
} else {
    // Mostrar formulario si no hay envío POST
    mostrarFormulario();
}

// Función para calcular los resultados
function calcularResultados($conn, $participante_id) {
    // Obtener las respuestas del participante
    $sql = "SELECT * FROM respuestas WHERE participante_id = $participante_id ORDER BY fecha_respuesta DESC LIMIT 1";
    $result = $conn->query($sql);
    $respuestas = $result->fetch_assoc();
    
    // Inicializar contadores
    $culturas_actuales = ['Clan' => 0, 'Adhocracia' => 0, 'Mercado' => 0, 'Jerarquía' => 0];
    $culturas_deseadas = ['Clan' => 0, 'Adhocracia' => 0, 'Mercado' => 0, 'Jerarquía' => 0];
    $aprendizajes = [
        'Aprendizaje continuo' => 0, 
        'Aprendizaje en equipo' => 0,
        'Dirección estratégica' => 0,
        'Empoderamiento' => 0,
        'Investigación y diálogo' => 0,
        'Sistema integrado' => 0
    ];
    
    // Mapeo de respuestas B (deseadas) a culturas y aprendizajes
    $mapeo_respuestas = [
        '1B' => ['A' => ['Jerarquía', 'Aprendizaje continuo'], 
                'B' => ['Clan', 'Investigación y diálogo'],
                'C' => ['Adhocracia', 'Aprendizaje en equipo'],
                'D' => ['Mercado', 'Dirección estratégica']],
        '2B' => ['A' => ['Jerarquía', 'Sistema integrado'], 
                'B' => ['Clan', 'Investigación y diálogo'],
                'C' => ['Adhocracia', 'Aprendizaje continuo'],
                'D' => ['Mercado', 'Aprendizaje en equipo']],
        '3B' => ['A' => ['Jerarquía', 'Sistema integrado'], 
                'B' => ['Clan', 'Aprendizaje en equipo'],
                'C' => ['Adhocracia', 'Dirección estratégica'],
                'D' => ['Mercado', 'Aprendizaje continuo']],
        '4B' => ['A' => ['Jerarquía', 'Dirección estratégica'], 
                'B' => ['Clan', 'Aprendizaje en equipo'],
                'C' => ['Adhocracia', 'Empoderamiento'],
                'D' => ['Mercado', 'Investigación y diálogo']],
        '5B' => ['A' => ['Jerarquía', 'Sistema integrado'], 
                'B' => ['Clan', 'Empoderamiento'],
                'C' => ['Adhocracia', 'Dirección estratégica'],
                'D' => ['Mercado', 'Investigación y diálogo']],
        '6B' => ['A' => ['Jerarquía', 'Sistema integrado'], 
                'B' => ['Clan', 'Empoderamiento'],
                'C' => ['Adhocracia', 'Dirección estratégica'],
                'D' => ['Mercado', 'Empoderamiento']],
        '7B' => ['A' => ['Jerarquía', 'Sistema integrado'], 
                'B' => ['Clan', 'Aprendizaje en equipo'],
                'C' => ['Adhocracia', 'Empoderamiento'],
                'D' => ['Mercado', 'Aprendizaje continuo']],
    ];
    
    // Contar culturas actuales (respuestas A)
    for ($i = 1; $i <= 7; $i++) {
        $pregunta = "p" . $i . "A"; 
        $culturas_actuales[$respuestas[$pregunta]]++;
    }
    
    // Contar culturas deseadas y aprendizajes (respuestas B)
    for ($i = 1; $i <= 7; $i++) {
        $pregunta = "p" . $i . "B"; 
        $respuesta = $respuestas[$pregunta];
        $mapeo = $mapeo_respuestas[$i.'B'][$respuesta];
        
        $culturas_deseadas[$mapeo[0]]++;
        $aprendizajes[$mapeo[1]]++;
    }
    
    // Insertar resultados en la base de datos
    $stmt = $conn->prepare("INSERT INTO resultados (
        participante_id,
        clan, adhocracia, mercado, jerarquia,
        aprendizaje_continuo, aprendizaje_en_equipo, direccion_estrategica,
        empoderamiento, investigacion_dialogo, sistema_integrado
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("iiiiiiiiiii", 
        $participante_id,
        $culturas_deseadas['Clan'], $culturas_deseadas['Adhocracia'], 
        $culturas_deseadas['Mercado'], $culturas_deseadas['Jerarquía'],
        $aprendizajes['Aprendizaje continuo'], $aprendizajes['Aprendizaje en equipo'],
        $aprendizajes['Dirección estratégica'], $aprendizajes['Empoderamiento'],
        $aprendizajes['Investigación y diálogo'], $aprendizajes['Sistema integrado']
    );
    
    $stmt->execute();
    $stmt->close();
    
    return [
        'cultura_actual' => array_search(max($culturas_actuales), $culturas_actuales),
        'cultura_deseada' => array_search(max($culturas_deseadas), $culturas_deseadas),
        'aprendizaje_principal' => array_search(max($aprendizajes), $aprendizajes),
        'detalles_culturas' => [
            'actual' => $culturas_actuales,
            'deseada' => $culturas_deseadas
        ],
        'detalles_aprendizaje' => $aprendizajes
    ];
}

//Función para mostrar resultados
function mostrarResultados($resultados) {
    $culturas_info = [
    'Clan' => [
        'tipo' => 'Organizativa',
        'diagnostico' => 'Entorno colaborativo donde prima la confianza, lealtad y sentido de familia. Los líderes actúan como mentores y se valora el desarrollo humano. El éxito se mide por el bienestar de las personas y la satisfacción del cliente.',
        'lider' => 'Facilitador: Promueve la participación. Mentor: Invierte en desarrollo individual. Team builder: Fomenta la cohesión.',
        'impulsores_valor' => 'Compromiso emocional. Comunicación abierta. Desarrollo continuo.',
        'teoria_efectividad' => 'La participación y el capital humano generan productividad a largo plazo (Teoría Y de McGregor).',
        'estrategias_calidad' => 'Programas de mentoría. Reuniones de feedback 360°. Encuestas de clima laboral. Celebración de rituales grupales.',
        'conclusion' => 'La cultura Clan es la más consolidada, con alto consenso en valores colaborativos. Ideal para sectores como educación o ONGs, pero puede necesitar reforzar enfoques estratégicos (ej: alinear objetivos individuales con metas organizacionales).'
    ],
    'Adhocracia' => [
        'tipo' => 'Organizativa',
        'diagnostico' => 'Entorno dinámico e innovador donde se premia la creatividad y la asunción de riesgos. Los líderes son visionarios y la organización se adapta rápidamente a cambios. El éxito se define por la innovación disruptiva.',
        'lider' => 'Visionario: Inspira con ideas audaces. Innovador: Experimenta y aprende del fracaso. Catalizador: Conecta talentos diversos.',
        'impulsores_valor' => 'Creatividad. Agilidad. Tolerancia al error.',
        'teoria_efectividad' => 'La innovación continua es clave para la supervivencia (Teoría de la Adaptación de Darwin aplicada a organizaciones).',
        'estrategias_calidad' => 'Hackathons y laboratorios de ideas. Presupuestos para proyectos piloto. Métricas de aprendizaje (ej: "tasa de experimentación"). Reducción de burocracia.',
        'conclusion' => 'Esta cultura es percibida como deseable pero inconsistente en su implementación. Sectores como startups o tecnología la priorizan, pero requiere equilibrar libertad con enfoque estratégico para evitar caos.'
    ],
    'Mercado' => [
        'tipo' => 'Organizativa',
        'diagnostico' => 'Entorno competitivo orientado a resultados tangibles (ventas, cuotas, ROI). Los líderes son exigentes y la organización se compara con rivales. El éxito equivale a ganar mercado.',
        'lider' => 'Competitivo: Enfocado en superar metas. Negociador: Busca acuerdos rentables. Result-oriented: Recompensa el desempeño.',
        'impulsores_valor' => 'Eficiencia. Rentabilidad. Posicionamiento.',
        'teoria_efectividad' => 'La competencia externa impulsa la excelencia (Teoría de la Eficiencia de Porter).',
        'estrategias_calidad' => 'Bonos por objetivos. Benchmarking constante. Entrenamiento en habilidades comerciales. CRM para tracking de desempeño.',
        'conclusion' => 'Necesaria en entornos altamente competitivos (ej: retail, finanzas), pero puede erosionar el bienestar si no se complementa con prácticas de Clan. Ideal para roles comerciales.'
    ],
    'Jerarquía' => [
        'tipo' => 'Organizativa',
        'diagnostico' => 'Entorno estructurado con reglas claras, procesos estandarizados y roles definidos. Los líderes son coordinadores que garantizan estabilidad. El éxito significa precisión y control de riesgos.',
        'lider' => 'Coordinador: Asegura el cumplimiento de normas. Administrador: Optimiza recursos. Planificador: Anticipa riesgos.',
        'impulsores_valor' => 'Predictibilidad. Estandarización. Seguridad.',
        'teoria_efectividad' => 'El control de procesos reduce errores (Teoría Burocrática de Weber).',
        'estrategias_calidad' => 'Certificaciones ISO. Manuales de procedimiento. Auditorías internas. Mapas de riesgos.',
        'conclusion' => 'Esencial en industrias reguladas (ej: salud, aeronáutica), pero puede limitar la innovación. Recomendable modernizarla con herramientas digitales (ej: automatización) para ganar agilidad.'
    ]
];

$aprendizajes_info = [
    'Aprendizaje continuo' => [
        'tipo' => 'Aprendizaje',
        'diagnostico' => 'La mejora constante es norma, no excepción. Los empleados dedican horas semanales a capacitarse sin necesidad de mandato.',
        'lider' => 'Role model: Comparte abiertamente sus propias brechas. Curiosity agent: Premia preguntas más que respuestas.',
        'impulsores_valor' => 'Bibliotecas de nanodegrees. Learning days obligatorios. Badges digitales por habilidades.',
        'teoria_efectividad' => 'En la economía del conocimiento, el aprendizaje es la única ventaja sostenible (Drucker, 1993).',
        'estrategias_calidad' => 'Suscripciones corporativas a Coursera/Pluralsight. Book clubs con autores invitados. Shadowing internacional.',
        'conclusion' => 'Marcador de cultura High-Performance. Común en tech (ej: Amazon). Requiere tiempo protegido en la agenda.'
    ],
    'Aprendizaje en equipo' => [
        'tipo' => 'Aprendizaje',
        'diagnostico' => 'Los equipos aprenden juntos mediante colaboración, retroalimentación continua y metas compartidas. Se valora la sinergia sobre el desempeño individual.',
        'lider' => 'Team learner: Diseña proyectos que requieran interdependencia. Feedback coach: Enseña a dar/recibir críticas constructivas.',
        'impulsores_valor' => 'Confianza psicológica. Metodologías ágiles (ej: retrospectivas). Herramientas colaborativas (Miro, Slack).',
        'teoria_efectividad' => 'Los equipos que aprenden superan a la suma de sus partes (Peter Senge, La Quinta Disciplina).',
        'estrategias_calidad' => 'Talleres de co-creación con roles rotativos. Plataformas para compartir tribal knowledge. Premios al "Mejor docente interno".',
        'conclusion' => 'Esencial para organizaciones matriciales. Riesgo: convertir el aprendizaje en actividad social sin resultados tangibles.'
    ],
    'Dirección estratégica' => [
        'tipo' => 'Aprendizaje',
        'diagnostico' => 'El aprendizaje está alineado con objetivos de negocio claros. Se invierte solo en lo que genera ventaja competitiva.',
        'lider' => 'Estratega: Vincula capacitación a KPIs. Business partner: Traduce lenguaje técnico a ROI.',
        'impulsores_valor' => 'Balanced Scorecard de aprendizaje. Customer-centric training. Alianzas con universidades para skills del futuro.',
        'teoria_efectividad' => 'El aprendizaje debe crear ventajas difíciles de imitar (Resource-Based View, Barney 1991).',
        'estrategias_calidad' => 'Certificaciones pagadas por resultados. Microlearning just-in-time. Planes de sucesión basados en skills.',
        'conclusion' => 'Crítico en consultorías y scale-ups. Puede descuidar desarrollo humano si se extremiza.'
    ],
    'Empoderamiento' => [
        'tipo' => 'Aprendizaje',
        'diagnostico' => 'Los empleados tienen autonomía para probar ideas, acceder a recursos y tomar decisiones sin jerarquías rígidas.',
        'lider' => 'Delegador estratégico: Asigna retos, no tareas. Abogado del riesgo: Protege a los equipos de sanciones por fracasos.',
        'impulsores_valor' => 'Presupuestos de innovación descentralizados. Mecanismos ágiles de aprobación (ej: pitch rápido). Intraemprendimiento.',
        'teoria_efectividad' => 'La autonomía es el mejor predictor de engagement (Teoría de la Autodeterminación, Deci & Ryan).',
        'estrategias_calidad' => 'Hackathons con presupuesto ejecutable. 20% time (ej: Google). Matriz de delegación (niveles de autonomía).',
        'conclusion' => 'Clave en startups y empresas digitales. Contraindicado en industrias altamente reguladas sin salvaguardas.'
    ],
    'Investigación y diálogo' => [
        'tipo' => 'Aprendizaje',
        'diagnostico' => 'La organización fomenta preguntas críticas, debates abiertos y exploración de supuestos. Las conversaciones se basan en datos y diversidad de perspectivas.',
        'lider' => 'Facilitador dialógico: Crea espacios seguros para discusiones incómodas. Investigador: Promueve el uso de evidencias, no de opiniones.',
        'impulsores_valor' => 'Curiosidad institucionalizada. Tolerancia al disenso. Métodos científicos (ej: experimentos controlados).',
        'teoria_efectividad' => 'El diálogo deliberativo genera inteligencia colectiva (Habermas, 1984).',
        'estrategias_calidad' => 'Foros mensuales "Sin preguntas tontas". Bibliotecas de casos de estudio con lecciones. Técnicas de pensamiento crítico (ej: Five Whys).',
        'conclusion' => 'Indicador de madurez organizacional. Común en universidades y empresas de I+D. Requiere equilibrarse con dirección estratégica para evitar parálisis por análisis.'
    ],
    'Sistema integrado' => [
        'tipo' => 'Aprendizaje',
        'diagnostico' => 'El aprendizaje se captura en sistemas digitales, procesos estandarizados y métricas para evitar la reinvención de la rueda.',
        'lider' => 'Arquitecto del conocimiento: Diseña flujos de información. Data-driven: Exige indicadores de transferencia (ej: % lecciones aplicadas).',
        'impulsores_valor' => 'Plataformas LMS (ej: Moodle). Bancos de conocimiento searchable. Learning analytics.',
        'teoria_efectividad' => 'Las organizaciones que codifican conocimiento superan a sus pares (Nonaka & Takeuchi, 1995).',
        'estrategias_calidad' => 'Wikis actualizados por incentivos. Podcasts internos con lecciones de proyectos. Mapas de competencias digitales.',
        'conclusion' => 'Típico de multinacionales y sector aeronáutico. Riesgo: burocratizar el aprendizaje.'
    ]
];
    // Calcular totales para porcentajes
    $total_cultura_actual = array_sum($resultados['detalles_culturas']['actual']);
    $total_cultura_deseada = array_sum($resultados['detalles_culturas']['deseada']);
    $total_aprendizaje = array_sum($resultados['detalles_aprendizaje']);

    // Evitar divisiones por cero
    $total_cultura_actual = $total_cultura_actual ?: 1;
    $total_cultura_deseada = $total_cultura_deseada ?: 1;
    $total_aprendizaje = $total_aprendizaje ?: 1;

    // Preparar datos para los gráficos
    $data_cultura = [];
    $culturas = ['Clan', 'Adhocracia', 'Mercado', 'Jerarquía']; // Definir el orden de las culturas
    $max_cultura_value = 0;  // Inicializar el valor máximo para la cultura
    foreach ($culturas as $cultura) {
        $actual = round(($resultados['detalles_culturas']['actual'][$cultura] / $total_cultura_actual) * 100, 2);
        $deseada = round(($resultados['detalles_culturas']['deseada'][$cultura] / $total_cultura_deseada) * 100, 2);
        $data_cultura['actual'][] = $actual;
        $data_cultura['deseada'][] = $deseada;
        $max_cultura_value = max($max_cultura_value, $actual, $deseada); // Actualizar el valor máximo
    }

    $data_aprendizaje = [];
    $aprendizaje_labels = []; // Para almacenar las etiquetas de aprendizaje
    $max_aprendizaje_value = 0; // Inicializar el valor máximo para el aprendizaje
    if (isset($resultados['detalles_aprendizaje']) && is_array($resultados['detalles_aprendizaje'])) {
        foreach ($resultados['detalles_aprendizaje'] as $dimension => $puntuacion) {
            $puntuacion_redondeada = round(($puntuacion / $total_aprendizaje) * 100, 2);
            $data_aprendizaje[] = $puntuacion_redondeada;
            $aprendizaje_labels[] = $dimension; // Guardar el nombre de la dimensión
            $max_aprendizaje_value = max($max_aprendizaje_value, $puntuacion_redondeada); // Actualizar el valor máximo
        }
    }

    // Redondear hacia arriba al siguiente múltiplo de 10
    $max_cultura = ceil($max_cultura_value / 10) * 10;
    $max_aprendizaje = ceil($max_aprendizaje_value / 10) * 10;

    $json_cultura = json_encode($data_cultura);
    $json_aprendizaje = json_encode($data_aprendizaje);
    $json_aprendizaje_labels = json_encode($aprendizaje_labels);

    // Obtener la información de la cultura predominante actual y deseada
    $cultura_actual = $resultados['cultura_actual'];
    $cultura_deseada = $resultados['cultura_deseada'];
    $aprendizaje_principal = $resultados['aprendizaje_principal'];

    // Comienza el HTML
    echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Resultados del Diagnóstico</title>
    <script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 1200px;  /* Aumentado de 800px a 1200px */
            margin: 0 auto;
            padding: 20px;
            background-image: url('https://static.vecteezy.com/system/resources/previews/006/852/864/non_2x/abstract-colorful-different-form-background-free-vector.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
        }
        .contenido {
            background: rgba(255, 255, 255, 0.95); /* Más opaco para mejor legibilidad */
            padding: 30px;  /* Más padding */
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .resultado {
            background-color: #f8f9fa;  /* Color más claro */
            padding: 25px;
            margin-bottom: 30px;
            border-radius: 10px;
            border-left: 5px solid #4e73df;
        }
        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1fr;  /* Dos columnas */
            gap: 30px;  /* Espacio entre columnas */
            margin-bottom: 30px;
        }
        .cultura-box {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 0.95em;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background-color: #f2f2f2;
            font-weight: 600;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 2.2em;
        }
        h2 {
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-top: 0;
            font-size: 1.6em;
        }
        h3 {
            color: #3a5169;
            margin-top: 25px;
        }
        strong {
            color: #4e73df;
        }
        #chartCultura{
            width: 100%;
            height: 400px;
            margin-top: 30px;
        }
        #chartAprendizaje{
            width: 20%;
            height: 400px;
            margin-top: 30px;
        }
        .conclusion-box {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 5px solid #1cc88a;
        }
        .contenedor-grafico-aprendizajes{
            width: 50%;
            margin:auto;
        }
    </style>
</head>
<body>
    <div class='contenido'>
        <h1>Resultados de tu Diagnóstico Organizacional</h1>
        
        <div class='resultado'>
            <h2>Cultura Organizacional</h2>
            
            <div class='grid-container'>
                <!-- Cultura Actual -->
                <div class='cultura-box'>
                    <h3>Cultura Actual Predominante: {$cultura_actual}</h3>";
                    
                    if (isset($culturas_info[$cultura_actual]['diagnostico'])) {
                        echo "<p><strong>Diagnóstico:</strong><br>".htmlspecialchars($culturas_info[$cultura_actual]['diagnostico'])."</p>";
                    }
                    if (isset($culturas_info[$cultura_actual]['lider'])) {
                        echo "<p><strong>Líder:</strong><br>".htmlspecialchars($culturas_info[$cultura_actual]['lider'])."</p>";
                    }
                    if (isset($culturas_info[$cultura_actual]['impulsores_valor'])) {
                        echo "<p><strong>Impulsores de valor:</strong><br>".htmlspecialchars($culturas_info[$cultura_actual]['impulsores_valor'])."</p>";
                    }
                    if (isset($culturas_info[$cultura_actual]['teoria_efectividad'])) {
                        echo "<p><strong>Teoría de la efectividad:</strong><br>".htmlspecialchars($culturas_info[$cultura_actual]['teoria_efectividad'])."</p>";
                    }
                    if (isset($culturas_info[$cultura_actual]['estrategias_calidad'])) {
                        echo "<p><strong>Estrategias de calidad:</strong><br>".htmlspecialchars($culturas_info[$cultura_actual]['estrategias_calidad'])."</p>";
                    }
                    
                echo "</div>
                
                <!-- Cultura Deseada -->
                <div class='cultura-box'>
                    <h3>Cultura Deseada Predominante: {$cultura_deseada}</h3>";
                    
                    if (isset($culturas_info[$cultura_deseada]['diagnostico'])) {
                        echo "<p><strong>Diagnóstico:</strong><br>".htmlspecialchars($culturas_info[$cultura_deseada]['diagnostico'])."</p>";
                    }
                    if (isset($culturas_info[$cultura_deseada]['lider'])) {
                        echo "<p><strong>Líder:</strong><br>".htmlspecialchars($culturas_info[$cultura_deseada]['lider'])."</p>";
                    }
                    if (isset($culturas_info[$cultura_deseada]['impulsores_valor'])) {
                        echo "<p><strong>Impulsores de valor:</strong><br>".htmlspecialchars($culturas_info[$cultura_deseada]['impulsores_valor'])."</p>";
                    }
                    if (isset($culturas_info[$cultura_deseada]['teoria_efectividad'])) {
                        echo "<p><strong>Teoría de la efectividad:</strong><br>".htmlspecialchars($culturas_info[$cultura_deseada]['teoria_efectividad'])."</p>";
                    }
                    if (isset($culturas_info[$cultura_deseada]['estrategias_calidad'])) {
                        echo "<p><strong>Estrategias de calidad:</strong><br>".htmlspecialchars($culturas_info[$cultura_deseada]['estrategias_calidad'])."</p>";
                    }
                    
                echo "</div>
            </div>
            
            <h3>Detalle de Culturas</h3>
            <div class='grid-container'>
                <table>
                <tr>
                    <th>Tipo Cultural</th>
                    <th>Actual (%)</th>
                    <th>Deseada (%)</th>
                </tr>";

                $culturas = ['Clan', 'Adhocracia', 'Mercado', 'Jerarquía'];
                foreach ($culturas as $cultura) {
                    $valor_actual = round(($resultados['detalles_culturas']['actual'][$cultura] / $total_cultura_actual) * 100, 2);
                    $valor_deseado = round(($resultados['detalles_culturas']['deseada'][$cultura] / $total_cultura_deseada) * 100, 2);
                    echo "<tr>
                        <td>$cultura</td>
                        <td>$valor_actual%</td>
                        <td>$valor_deseado%</td>
                    </tr>";
                }

            echo "</table>
            <div>
                <canvas id='chartCultura'></canvas>
            </div>
            
        </div>
            </div>

        <div class='resultado'>
            <h2>Cultura de Aprendizaje</h2>
            <div class='grid-container'>
                <div class='cultura-box'>
                    <h3>Dimensión Principal: {$aprendizaje_principal}</h3>";
                    
                    if (isset($aprendizajes_info[$aprendizaje_principal]['diagnostico'])) {
                        echo "<p><strong>Resumen:</strong><br>".htmlspecialchars($aprendizajes_info[$aprendizaje_principal]['diagnostico'])."</p>";
                    }
                    if (isset($aprendizajes_info[$aprendizaje_principal]['lider'])) {
                        echo "<p><strong>Líder:</strong><br>".htmlspecialchars($aprendizajes_info[$aprendizaje_principal]['lider'])."</p>";
                    }
                    if (isset($aprendizajes_info[$aprendizaje_principal]['impulsores_valor'])) {
                        echo "<p><strong>Impulsores de valor:</strong><br>".htmlspecialchars($aprendizajes_info[$aprendizaje_principal]['impulsores_valor'])."</p>";
                    }
                    if (isset($aprendizajes_info[$aprendizaje_principal]['teoria_efectividad'])) {
                        echo "<p><strong>Teoría de la efectividad:</strong><br>".htmlspecialchars($aprendizajes_info[$aprendizaje_principal]['teoria_efectividad'])."</p>";
                    }
                    if (isset($aprendizajes_info[$aprendizaje_principal]['estrategias_calidad'])) {
                        echo "<p><strong>Estrategias de calidad:</strong><br>".htmlspecialchars($aprendizajes_info[$aprendizaje_principal]['estrategias_calidad'])."</p>";
                    }
                    
                echo "</div>
                <div>
                    <h3>Detalle de Dimensiones</h3>
                    <table>
                        <tr>
                            <th>Dimensión</th>
                            <th>Puntuación (%)</th>
                        </tr>";

                        foreach ($resultados['detalles_aprendizaje'] as $dimension => $puntuacion) {
                            $puntuacion_porcentaje = round(($puntuacion / $total_aprendizaje) * 100, 2);
                            echo "<tr>
                                <td>$dimension</td>
                                <td>$puntuacion_porcentaje%</td>
                            </tr>";
                        }

                    echo "</table>
                </div>
            </div>
            <div class='contenedor-grafico-aprendizajes'>
                <canvas id='chartAprendizaje'></canvas>
            </div>
            
        </div>

        <div class='conclusion-box'>
            <h2>Conclusión</h2>
            <p>Según tus respuestas, <br>" . htmlspecialchars($culturas_info[$cultura_actual]['conclusion']) . "<br><br>Pero preferirías: <br>" . htmlspecialchars($culturas_info[$cultura_deseada]['conclusion']) . "<br><br>En cuanto al entorno de aprendizaje que prefieres, es más: <br>" . htmlspecialchars($aprendizajes_info[$aprendizaje_principal]['conclusion']) . "</p>
        </div>
    </div>

        </div>

        <script>
            var dataCultura = ". $json_cultura . ";
            var dataAprendizaje = ". $json_aprendizaje . ";
            var aprendizajeLabels = ". $json_aprendizaje_labels . "; // Etiquetas para el gráfico de aprendizaje
            
            // Extraer los valores de data_cultura para 'actual' y 'deseada'
            var actualData = dataCultura.actual;
            var deseadaData = dataCultura.deseada;

            // Nombres de las culturas para el eje
            var culturaLabels = ['Clan', 'Adhocracia', 'Mercado', 'Jerarquía'];

            var maxCultura = ".$max_cultura."; // Valor máximo dinámico para el eje del gráfico de cultura
            var maxAprendizaje = ".$max_aprendizaje.";   // Valor máximo dinámico para el eje del gráfico de aprendizaje

            // Configuración del gráfico de cultura
            const ctxCultura = document.getElementById('chartCultura').getContext('2d');
            const chartCultura = new Chart(ctxCultura, {
                type: 'radar',
                data: {
                    labels: culturaLabels,
                    datasets: [{
                        label: 'Actual',
                        data: actualData,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        pointRadius: 3, // Ajustar el radio de los puntos
                        pointBorderWidth: 2, // Ajustar el ancho del borde de los puntos
                        pointStyle: 'circle' // Mostrar los puntos como círculos
                    }, {
                        label: 'Deseada',
                        data: deseadaData,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        pointRadius: 3, // Ajustar el radio de los puntos
                        pointBorderWidth: 2, // Ajustar el ancho del borde de los puntos
                        pointStyle: 'circle' // Mostrar los puntos como círculos
                    }]
                },
                options: {
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: maxCultura, // Usar el valor máximo dinámico
                            ticks: {
                                display: true,
                                stepSize: 20, // Ajustar el tamaño del paso de los ticks
                                backdropColor: 'transparent' // Hacer el fondo de los ticks transparente
                            }
                        }
                    },
                    elements: {
                        line: {
                            tension: 0.4 // Ajustar la tensión de la línea para redondear las esquinas
                        }
                    }
                }
            });

            // Configuración del gráfico de aprendizaje
            const ctxAprendizaje = document.getElementById('chartAprendizaje').getContext('2d');
            const chartAprendizaje = new Chart(ctxAprendizaje, {
                type: 'radar',
                data: {
                    labels: aprendizajeLabels, // Usar las etiquetas correctas
                    datasets: [{
                        label: 'Puntuación',
                        data: dataAprendizaje,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        pointRadius: 3, // Ajustar el radio de los puntos
                        pointBorderWidth: 2, // Ajustar el ancho del borde de los puntos
                        pointStyle: 'circle' // Mostrar los puntos como círculos
                    }]
                },
                options: {
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: maxAprendizaje, // Usar el valor máximo dinámico
                            ticks: {
                                display: true,
                                stepSize: 20, // Ajustar el tamaño del paso de los ticks
                                backdropColor: 'transparent' // Hacer el fondo de los ticks transparente
                            }
                        }
                    },
                    elements: {
                        line: {
                            tension: 0.4 // Ajustar la tensión de la línea para redondear las esquinas
                        }
                    }
                }
            });
        </script>
    </body>
    </html>";
}


// Función para mostrar el formulario
function mostrarFormulario() {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Diagnóstico organizacional</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
                background-image: url('https://static.vecteezy.com/system/resources/previews/006/852/864/non_2x/abstract-colorful-different-form-background-free-vector.jpg');
                background-size: cover;
                background-position: center;
                background-attachment: fixed;
                background-repeat: no-repeat;
            }
            .pregunta {
                margin-bottom: 30px;
                padding: 15px;
                background-color: #f9f9f9;
                border-radius: 5px;
            }
            .opciones {
                margin-left: 20px;
            }
            .grupo {
                margin-bottom: 15px;
            }
            h2 {
                color: #2c3e50;
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
            }
            form {
                background: rgba(255, 255, 255, 0.9); /* Fondo blanco semitransparente */
                padding: 20px;
                border-radius: 10px;
            }
        </style>
    </head>
    <body>

        <h1>Diagnóstico de cultura organizacional y aprendizaje</h1>
        <form method='post' action='diagnosticoCultura.php'>
            
            <h2>Datos del Participante</h2>
            <div class='grupo'>
                <label>Nombre:</label>
                <input type='text' name='nombre' required>
            </div>
            <div class='grupo'>
                <label>Email:</label>
                <input type='email' name='email' required>
            </div>
            <div class='grupo'>
                <label>Empresa:</label>
                <input type='text' name='empresa'>
            </div>
            
            <h2>Cuestionario</h2>
            <p>Para cada situación, selecciona cómo es ACTUALMENTE en tu organización y cómo TE GUSTARÍA QUE FUERA.</p>";
            $clan=$mercado=$adhocracia=$jerarquia=$aprendizaje_continuo=$aprendizaje_equipo=$direccion_estrategica=$empoderamiento=$investigacion_dialogo=$sistema_integrado=0;
    // Aquí irían todas las preguntas del cuestionario
    // PREGUNTA 1: Toma de decisiones
echo "<div class='pregunta'>
        <h3>1. Toma de decisiones</h3>
        <p>Cuando hay que tomar una decisión importante...</p>
        
        <h4>Actualmente:</h4>
        <div class='opciones'>
            <label><input type='radio' name='p1A' value='Jerarquía' required> Los jefes deciden sin consultar al equipo</label><br>
                    <label><input type='radio' name='p1A' value='Clan'> Se discute en grupo, pero a veces no se llega a un acuerdo claro</label><br>
                    <label><input type='radio' name='p1A' value='Mercado'> Se elige la opción que genere más beneficios, aunque no guste a todos</label><br>
                    <label><input type='radio' name='p1A' value='Adhocracia'> Se prueban ideas nuevas, pero falta seguimiento</label>
        </div>
        
        <h4>Me gustaría que:</h4>
        <div class='opciones'>
                    <label><input type='radio' name='p1B' value='A' required> Prefiero que los líderes tomen decisiones basadas en datos históricos</label><br>
                    <label><input type='radio' name='p1B' value='B'> Quisiera que el equipo consensuara la mejor opción mediante debate abierto</label><br>
                    <label><input type='radio' name='p1B' value='C'> Desearía equipos autónomos que prototipen soluciones</label><br>
                    <label><input type='radio' name='p1B' value='D'> Me gustaría vincular decisiones a objetivos estratégicos claros</label>
        </div>
    </div>";

// PREGUNTA 2: Comunicación interna
echo "<div class='pregunta'>
        <h3>2. Comunicación interna</h3>
        <p>Cuando hay que compartir información clave...</p>
        
        <h4>Actualmente:</h4>
        <div class='opciones'>
            <label><input type='radio' name='p2A' value='Jerarquía' required> Solo los directivos comunican por correo formal</label><br>
            <label><input type='radio' name='p2A' value='Clan'> Hay rumores y conversaciones informales, pero poca claridad</label><br>
            <label><input type='radio' name='p2A' value='Mercado'> Se comparte solo lo que afecta a los objetivos financieros</label><br>
            <label><input type='radio' name='p2A' value='Adhocracia'> Hay libertad para opinar, pero sin orden</label>
        </div>
        
        <h4>Me gustaría que:</h4>
        <div class='opciones'>
            <label><input type='radio' name='p2B' value='A' required> Prefiero canales oficiales con información verificada</label><br>
            <label><input type='radio' name='p2B' value='B'> Quisiera foros donde todos aporten sin miedo</label><br>
            <label><input type='radio' name='p2B' value='C'> Desearía transparencia radical con datos en tiempo real</label><br>
            <label><input type='radio' name='p2B' value='D'> Me gustaría plataformas colaborativas para innovar</label>
        </div>
    </div>";

// PREGUNTA 3: Manejo de errores
echo "<div class='pregunta'>
        <h3>3. Manejo de errores</h3>
        <p>Ante la aparición evidente de un error...</p>
        
        <h4>Actualmente:</h4>
        <div class='opciones'>
            <label><input type='radio' name='p3A' value='Jerarquía' required> Se buscan culpables</label><br>
            <label><input type='radio' name='p3A' value='Clan'> Se habla del error, pero no se corrige</label><br>
            <label><input type='radio' name='p3A' value='Mercado'> Se oculta para proteger reputaciones</label><br>
            <label><input type='radio' name='p3A' value='Adhocracia'> Se ignora porque \"el fracaso es normal\"</label>
        </div>
        
        <h4>Me gustaría que:</h4>
        <div class='opciones'>
            <label><input type='radio' name='p3B' value='A' required> Prefiero analizar causas raíz y mejorar procesos</label><br>
            <label><input type='radio' name='p3B' value='B'> Quisiera reflexiones grupales sin culpas, pero sí con responsables</label><br>
            <label><input type='radio' name='p3B' value='C'> Desearía corregir rápido y comunicar soluciones</label><br>
            <label><input type='radio' name='p3B' value='D'> Me gustaría documentar fracasos como casos de estudio</label>
        </div>
    </div>";

// PREGUNTA 4: Innovación
echo "<div class='pregunta'>
        <h3>4. Innovación</h3>
        <p>Si hay necesidad de aportar nuevas ideas...</p>
        
        <h4>Actualmente:</h4>
        <div class='opciones'>
            <label><input type='radio' name='p4A' value='Jerarquía' required> Solo se innova si lo ordena la dirección</label><br>
            <label><input type='radio' name='p4A' value='Clan'> Hay ideas, pero faltan recursos</label><br>
            <label><input type='radio' name='p4A' value='Mercado'> Se copia a la competencia</label><br>
            <label><input type='radio' name='p4A' value='Adhocracia'> Mucha experimentación sin foco</label>
        </div>
        
        <h4>Me gustaría que:</h4>
        <div class='opciones'>
            <label><input type='radio' name='p4B' value='A' required> Prefiero un comité que evalúe ideas con métricas</label><br>
            <label><input type='radio' name='p4B' value='B'> Quisiera talleres creativos con todos los departamentos</label><br>
            <label><input type='radio' name='p4B' value='C'> Desearía que cualquier empleado pudiera proponer y validar ideas</label><br>
            <label><input type='radio' name='p4B' value='D'> Me gustaría tiempo libre para proyectos personales</label>
        </div>
    </div>";

// PREGUNTA 5: Liderazgo
echo "<div class='pregunta'>
        <h3>5. Liderazgo</h3>
        <p>Cuando los líderes o managers interactúan con sus equipos...</p>
        
        <h4>Actualmente:</h4>
        <div class='opciones'>
            <label><input type='radio' name='p5A' value='Jerarquía' required> Tienen carácter autoritario y controlador</label><br>
            <label><input type='radio' name='p5A' value='Clan'> Son amigables pero poco decisivos</label><br>
            <label><input type='radio' name='p5A' value='Mercado'> Están muy orientados solo a resultados</label><br>
            <label><input type='radio' name='p5A' value='Adhocracia'> Son inspiradores pero caóticos</label>
        </div>
        
        <h4>Me gustaría que:</h4>
        <div class='opciones'>
            <label><input type='radio' name='p5B' value='A' required> Prefiero líderes que establezcan procesos claros</label><br>
            <label><input type='radio' name='p5B' value='B'> Quisiera que los equipos tuvieran autonomía para tomar decisiones</label><br>
            <label><input type='radio' name='p5B' value='C'> Desearía jefes que desafíen a superar metas</label><br>
            <label><input type='radio' name='p5B' value='D'> Me gustaría líderes que fomenten pensar \"fuera de la caja\"</label>
        </div>
    </div>";

// PREGUNTA 6: Gestión del tiempo
echo "<div class='pregunta'>
        <h3>6. Gestión del tiempo</h3>
        <p>Cuando se organizan las jornadas y plazos de trabajo en la organización...</p>
        
        <h4>Actualmente:</h4>
        <div class='opciones'>
            <label><input type='radio' name='p6A' value='Jerarquía' required> Horarios rígidos sin flexibilidad</label><br>
            <label><input type='radio' name='p6A' value='Clan'> Mucha libertad, pero falta productividad</label><br>
            <label><input type='radio' name='p6A' value='Mercado'> Se valora solo el tiempo que genera ingresos</label><br>
            <label><input type='radio' name='p6A' value='Adhocracia'> Cada uno gestiona su tiempo, pero sin coordinación</label>
        </div>
        
        <h4>Me gustaría que:</h4>
        <div class='opciones'>
            <label><input type='radio' name='p6B' value='A' required> Prefiero sistemas que optimicen el tiempo basados en datos</label><br>
            <label><input type='radio' name='p6B' value='B'> Quisiera autonomía para elegir cuándo trabajar</label><br>
            <label><input type='radio' name='p6B' value='C'> Desearía medir el tiempo por resultados, no por horas</label><br>
            <label><input type='radio' name='p6B' value='D'> Me gustaría que cada equipo diseñe su modelo ideal</label>
        </div>
    </div>";

// PREGUNTA 7: Evaluación del desempeño
echo "<div class='pregunta'>
        <h3>7. Evaluación del desempeño</h3>
        <p>Cuando se evalúa el trabajo y los aportes de los colaboradores...</p>
        
        <h4>Actualmente:</h4>
        <div class='opciones'>
            <label><input type='radio' name='p7A' value='Jerarquía' required> Se basa en cumplir órdenes</label><br>
            <label><input type='radio' name='p7A' value='Clan'> Es subjetiva y poco transparente</label><br>
            <label><input type='radio' name='p7A' value='Mercado'> Solo importan los números, sin contexto</label><br>
            <label><input type='radio' name='p7A' value='Adhocracia'> No hay criterios claros</label>
        </div>
        
        <h4>Me gustaría que:</h4>
        <div class='opciones'>
            <label><input type='radio' name='p7B' value='A' required> Prefiero métricas objetivas alineadas a procesos</label><br>
            <label><input type='radio' name='p7B' value='B'> Quisiera feedback 360° con foco en desarrollo</label><br>
            <label><input type='radio' name='p7B' value='C'> Desearía bonos por metas que desarrollen habilidades</label><br>
            <label><input type='radio' name='p7B' value='D'> Me gustaría que se reconociera el aprendizaje autodirigido</label>
        </div>
    </div>";
    
    
    echo "<input type='submit' value='Enviar Diagnóstico'>
        </form>
    </body>
    </html>";
}

$conn->close();
?>