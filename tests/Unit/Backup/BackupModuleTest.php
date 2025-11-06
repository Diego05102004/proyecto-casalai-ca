<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../modelo/backup.php';

final class BackupModuleTest extends TestCase
{
    private string $backupDir;

    protected function setUp(): void
    {
        $this->backupDir = realpath(__DIR__ . '/../../../') . '/db/backup/';
        $this->limpiarCarpeta();
    }

    private function limpiarCarpeta(): void
    {
        // Elimina archivos previos en db/backup sin borrar la carpeta padre
        if (is_dir($this->backupDir)) {
            $files = @scandir($this->backupDir) ?: [];
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') { continue; }
                @unlink($this->backupDir . $f);
            }
        }
    }

    public function testListarFiltraSoloSQL(): void
    {
        if (!is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0775, true);
        }
        file_put_contents($this->backupDir . 'a.sql', '-- dump A');
        file_put_contents($this->backupDir . 'b.SQL', '-- dump B'); // No coincide, case-sensitive por regex
        file_put_contents($this->backupDir . 'c.txt', 'texto');
        file_put_contents($this->backupDir . 'd.sql.bak', 'otro');

        $b = new Backup('P');
        $lista = $b->listar();
        $this->assertIsArray($lista);
        sort($lista);
        $this->assertSame(['a.sql'], $lista);
    }

    public function testGenerarCreaCarpetaAunqueFalleDump(): void
    {
        // Asegurar que la carpeta no exista para verificar su creación
        if (is_dir($this->backupDir)) {
            // Intentar borrar si está vacía; de lo contrario vaciar
            $this->limpiarCarpeta();
            @rmdir($this->backupDir);
        }
        $this->assertFalse(is_dir($this->backupDir), 'La carpeta de backup debería no existir antes de generar');

        $b = new Backup('P');
        $ok = $b->generar('prueba_unit.sql');
        // El resultado de $ok depende de mysqldump; no afirmamos true/false
        $this->assertTrue(is_dir($this->backupDir), 'La carpeta de backup debió ser creada por generar()');
    }

    public function testRestaurarConArchivoInexistenteDevuelveFalse(): void
    {
        $b = new Backup('P');
        $ok = $b->restaurar('archivo_que_no_existe.sql');
        $this->assertFalse($ok);
    }
}
