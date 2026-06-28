<?php
// Carrega as configurações e a classe do MikroTik
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/Mikrotik.php';

$mk = new Mikrotik();

// Vamos inventar um MAC e um IP para o teste
$mac_teste = "5C:F9:38:11:22:44";
$ip_teste = "100.10.10.60";

echo "<h1>Teste de Conexão REST - MikroTik</h1>";
echo "Enviando ordem para liberar o MAC <strong>$mac_teste</strong>...<br><br>";

// Dispara a função que criamos
$sucesso = $mk->liberarMac($mac_teste, $ip_teste);

if ($sucesso) {
    echo "<h2 style='color: green;'>✅ SUCESSO ABSOLUTO!</h2>";
    echo "O seu PHP entrou no MikroTik via REST e criou a regra!<br>";
    echo "Vá ao Winbox em <b>IP > Hotspot > IP Bindings</b> e veja se o MAC $mac_teste está lá com um comentário amarelo!";
} else {
    echo "<h2 style='color: red;'>❌ FALHOU!</h2>";
    echo "A conexão não foi bem sucedida.<br>";
    echo "Abra o arquivo <b>webhook_log.txt</b> na sua hospedagem para ver o erro exato que o MikroTik devolveu.";
}
?>