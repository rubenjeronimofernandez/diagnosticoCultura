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

    echo "Respuestas guardadas correctamente con ID: " . $respuesta_id;
    
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

// Función para mostrar resultados
function mostrarResultados($resultados) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Resultados del Diagnóstico</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto; padding: 20px; }
            .resultado { background-color: #f5f5f5; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
            .grafico { height: 20px; background-color: #e0e0e0; margin: 10px 0; border-radius: 3px; }
            .barra { height: 100%; background-color: #4CAF50; border-radius: 3px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <h1>Resultados de tu Diagnóstico Organizacional</h1>
        
        <div class='resultado'>
            <h2>Cultura Organizacional</h2>
            <p><strong>Cultura Actual Predominante:</strong> {$resultados['cultura_actual']}</p>
            <p><strong>Cultura Deseada Predominante:</strong> {$resultados['cultura_deseada']}</p>
            
            <h3>Detalle de Culturas</h3>
            <table>
                <tr>
                    <th>Tipo Cultural</th>
                    <th>Actual</th>
                    <th>Deseada</th>
                </tr>";
    
    foreach ($resultados['detalles_culturas']['actual'] as $cultura => $valor) {
        $valor_deseado = $resultados['detalles_culturas']['deseada'][$cultura];
        echo "<tr>
                <td>$cultura</td>
                <td>$valor</td>
                <td>$valor_deseado</td>
              </tr>";
    }
    
    echo "</table>
        </div>
        
        <div class='resultado'>
            <h2>Cultura de Aprendizaje</h2>
            <p><strong>Dimensión Principal:</strong> {$resultados['aprendizaje_principal']}</p>
            
            <h3>Detalle de Dimensiones</h3>
            <table>
                <tr>
                    <th>Dimensión</th>
                    <th>Puntuación</th>
                </tr>";
    
    foreach ($resultados['detalles_aprendizaje'] as $dimension => $puntuacion) {
        echo "<tr>
                <td>$dimension</td>
                <td>$puntuacion</td>
              </tr>";
    }
    
    echo "</table>
        </div>
        
        <div class='resultado'>
            <h2>Recomendaciones</h2>";
    
    // Aquí puedes añadir recomendaciones específicas basadas en los resultados
    echo "<p>Basado en tus resultados, te recomendamos trabajar en...</p>";
    
    echo "</div>
    </body>
    </html>";
}

// Función para mostrar el formulario
function mostrarFormulario() {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Diagnóstico Organizacional</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto; padding: 20px; }
            .pregunta { margin-bottom: 30px; padding: 15px; background-color: #f9f9f9; border-radius: 5px; }
            .opciones { margin-left: 20px; }
            .grupo { margin-bottom: 15px; }
            h2 { color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        </style>
    </head>
    <body>
        <h1>Diagnóstico de Cultura Organizacional y Aprendizaje</h1>
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