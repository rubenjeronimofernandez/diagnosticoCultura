<?php
// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "diagnostico_organizacional";

// Conexión a la base de datos
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Función para mostrar los resultados
function mostrarResultados($participante_id, $conn) {
    // Obtener los últimos resultados del participante
    $sql = "SELECT * FROM resultados WHERE participante_id = $participante_id ORDER BY fecha_analisis DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        die("No se encontraron resultados para este participante.");
    }
    
    $resultados = $result->fetch_assoc();
    
    // Obtener información del participante
    $sql_participante = "SELECT * FROM participantes WHERE id = $participante_id";
    $participante = $conn->query($sql_participante)->fetch_assoc();
    
    // Calcular porcentajes para gráficos
    $total_cultura = array_sum([
        $resultados['clan'], 
        $resultados['adhocracia'],
        $resultados['mercado'],
        $resultados['jerarquia']
    ]);
    
    $total_aprendizaje = array_sum([
        $resultados['aprendizaje_continuo'],
        $resultados['aprendizaje_en_equipo'],
        $resultados['direccion_estrategica'],
        $resultados['empoderamiento'],
        $resultados['investigacion_dialogo'],
        $resultados['sistema_integrado']
    ]);
    
    // Determinar cultura y aprendizaje predominante
    $culturas = [
        'Clan' => $resultados['clan'],
        'Adhocracia' => $resultados['adhocracia'],
        'Mercado' => $resultados['mercado'],
        'Jerarquía' => $resultados['jerarquia']
    ];
    
    $aprendizajes = [
        'Aprendizaje continuo' => $resultados['aprendizaje_continuo'],
        'Aprendizaje en equipo' => $resultados['aprendizaje_en_equipo'],
        'Dirección estratégica' => $resultados['direccion_estrategica'],
        'Empoderamiento' => $resultados['empoderamiento'],
        'Investigación y diálogo' => $resultados['investigacion_dialogo'],
        'Sistema integrado' => $resultados['sistema_integrado']
    ];
    
    $cultura_predominante = array_search(max($culturas), $culturas);
    $aprendizaje_predominante = array_search(max($aprendizajes), $aprendizajes);
    
    // Generar HTML de resultados
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Resultados del Diagnóstico</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 1000px;
                margin: 0 auto;
                padding: 20px;
                background-color: #f9f9f9;
            }
            .header {
                background-color: #2c3e50;
                color: white;
                padding: 20px;
                border-radius: 5px;
                margin-bottom: 30px;
                text-align: center;
            }
            .result-section {
                background-color: white;
                border-radius: 5px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            h2 {
                color: #2c3e50;
                border-bottom: 2px solid #eee;
                padding-bottom: 10px;
            }
            .bar-container {
                width: 100%;
                background-color: #ecf0f1;
                border-radius: 5px;
                margin: 10px 0;
            }
            .bar {
                height: 30px;
                border-radius: 5px;
                background-color: #3498db;
                text-align: center;
                color: white;
                line-height: 30px;
                font-weight: bold;
            }
            .culture-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            .learning-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
            }
            .highlight {
                background-color: #e3f2fd;
                padding: 15px;
                border-radius: 5px;
                border-left: 4px solid #2196f3;
                margin: 20px 0;
            }
            .recommendations {
                background-color: #e8f5e9;
                padding: 15px;
                border-radius: 5px;
                border-left: 4px solid #4caf50;
            }
            .tag {
                display: inline-block;
                background-color: #e0e0e0;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 0.8em;
                margin-right: 5px;
            }
            .primary-tag {
                background-color: #2196f3;
                color: white;
            }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>Resultados del Diagnóstico Organizacional</h1>
            <p>Participante: {$participante['nombre']} | Empresa: {$participante['empresa']}</p>
        </div>
        
        <div class='result-section'>
            <h2>Resumen de Resultados</h2>
            
            <div class='highlight'>
                <h3>Cultura Organizacional Predominante</h3>
                <p><span class='tag primary-tag'>{$cultura_predominante}</span></p>
                
                <h3>Cultura de Aprendizaje Predominante</h3>
                <p><span class='tag primary-tag'>{$aprendizaje_predominante}</span></p>
            </div>
        </div>
        
        <div class='result-section'>
            <h2>Detalle de Cultura Organizacional</h2>
            <div class='culture-grid'>";
    
    // Mostrar cada tipo cultural
    foreach ($culturas as $cultura => $valor) {
        $porcentaje = round(($valor / $total_cultura) * 100);
        echo "<div>
                <h3>{$cultura}</h3>
                <div class='bar-container'>
                    <div class='bar' style='width: {$porcentaje}%'>{$porcentaje}%</div>
                </div>
                <p>Puntuación: {$valor}</p>
              </div>";
    }
    
    echo "</div></div>
        
        <div class='result-section'>
            <h2>Detalle de Cultura de Aprendizaje</h2>
            <div class='learning-grid'>";
    
    // Mostrar cada dimensión de aprendizaje
    foreach ($aprendizajes as $dimension => $valor) {
        $porcentaje = round(($valor / $total_aprendizaje) * 100);
        echo "<div>
                <h3>{$dimension}</h3>
                <div class='bar-container'>
                    <div class='bar' style='width: {$porcentaje}%'>{$porcentaje}%</div>
                </div>
                <p>Puntuación: {$valor}</p>
              </div>";
    }
    
    echo "</div></div>
        
        <div class='result-section'>
            <h2>Recomendaciones</h2>
            <div class='recommendations'>";
    
    // Generar recomendaciones basadas en los resultados
    echo generarRecomendaciones($culturas, $aprendizajes);
    
    echo "</div></div>
        
        <div class='result-section'>
            <p><small>Fecha del diagnóstico: {$resultados['fecha_analisis']}</small></p>
        </div>
    </body>
    </html>";
}

// Función para generar recomendaciones
function generarRecomendaciones($culturas, $aprendizajes) {
    $recomendaciones = [];
    
    // Recomendaciones basadas en cultura
    $cultura_predominante = array_search(max($culturas), $culturas);
    
    switch ($cultura_predominante) {
        case 'Clan':
            $recomendaciones[] = "Fortalecer los programas de mentoría y desarrollo de liderazgo interno.";
            $recomendaciones[] = "Implementar sesiones regulares de feedback 360°.";
            break;
        case 'Adhocracia':
            $recomendaciones[] = "Establecer un fondo de innovación para proyectos experimentales.";
            $recomendaciones[] = "Crear un laboratorio de ideas con tiempo protegido para innovación.";
            break;
        case 'Mercado':
            $recomendaciones[] = "Desarrollar programas de incentivos alineados con objetivos estratégicos.";
            $recomendaciones[] = "Implementar benchmarking regular con competidores.";
            break;
        case 'Jerarquía':
            $recomendaciones[] = "Digitalizar procesos clave para mejorar la eficiencia.";
            $recomendaciones[] = "Implementar certificaciones de calidad (ISO 9001, etc.).";
            break;
    }
    
    // Recomendaciones basadas en aprendizaje
    $aprendizaje_predominante = array_search(max($aprendizajes), $aprendizajes);
    
    switch ($aprendizaje_predominante) {
        case 'Aprendizaje continuo':
            $recomendaciones[] = "Implementar una plataforma de e-learning con micro-cursos.";
            $recomendaciones[] = "Establecer un programa de desarrollo profesional individualizado.";
            break;
        case 'Aprendizaje en equipo':
            $recomendaciones[] = "Organizar talleres mensuales de co-creación entre departamentos.";
            $recomendaciones[] = "Crear un sistema de gestión del conocimiento colaborativo.";
            break;
        case 'Empoderamiento':
            $recomendaciones[] = "Implementar programas de intraemprendimiento con presupuesto asignado.";
            $recomendaciones[] = "Establecer políticas claras de delegación y autonomía.";
            break;
        // ... otros casos para cada dimensión de aprendizaje
    }
    
    // Recomendación para la brecha cultural si hay mucha diferencia
    if (max($culturas) - min($culturas) > 10) {
        $recomendaciones[] = "Considerar estrategias para equilibrar los diferentes tipos culturales en la organización.";
    }
    
    return "<ul><li>" . implode("</li><li>", $recomendaciones) . "</li></ul>";
}

// Procesamiento del formulario
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
    }
    $stmt->close();
    
    // Insertar respuestas (simplificado para el ejemplo)
    // Aquí iría la lógica para procesar todas las respuestas del formulario
    
    // Mostrar resultados
    mostrarResultados($participante_id, $conn);
    
} else {
    // Si se accede directamente al archivo sin enviar formulario
    echo "<h1>Error: No se han enviado datos</h1>";
    echo "<p>Por favor complete el formulario de diagnóstico primero.</p>";
}

$conn->close();
?>