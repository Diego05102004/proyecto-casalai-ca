<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../modelo/backup.php';

final class BackupControllerTest extends TestCase
{
    private string $backupDir;

    protected function setUp(): void
    {
        $this->backupDir = realpath(__DIR__ . '/../../../') . '/db/backup/';
        $this->limpiarCarpeta();
    }

    private function limpiarCarpeta(): void
    {
        if (is_dir($this->backupDir)) {
            $files = @scandir($this->backupDir) ?: [];
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') { continue; }
                @unlink($this->backupDir . $f);
            }
        }
    }

    public function testGenerarYListarIntegracion(): void
    {
        $b = new Backup('P');
        $ok = $b->generar('int_test.sql');
        // Asegura carpeta creada por generar()
        $this->assertTrue(is_dir($this->backupDir));

        // Crear además un archivo manual para verificar listar() independientemente de mysqldump
        if (!is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0775, true);
        }
        file_put_contents($this->backupDir . 'manual.sql', '-- dummy');

        $lista = $b->listar();
        $this->assertIsArray($lista);
        $this->assertContains('manual.sql', $lista);
        if ($ok) {
            $this->assertContains('int_test.sql', $lista);
            $this->assertGreaterThan(0, filesize($this->backupDir . 'int_test.sql'));
        }
    }

    public function testRestaurarArchivoInexistente(): void
    {
        $b = new Backup('P');
        $this->assertFalse($b->restaurar('no-existe.sql'));
    }

    public function testRestaurarArchivoVacioNoRompe(): void
    {
        if (!is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0775, true);
        }
        $fname = 'vacio.sql';
        file_put_contents($this->backupDir . $fname, '');
        $b = new Backup('P');
        $res = $b->restaurar($fname);
        $this->assertIsBool($res);
    }
}
