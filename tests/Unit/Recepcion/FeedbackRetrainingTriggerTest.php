<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Recepcion;

final class FeedbackRetrainingTriggerTest extends TestCase
{
    private $csvPath;
    private $recepcion;

    protected function setUp(): void
    {
        $this->csvPath = tempnam(sys_get_temp_dir(), 'recepcion-feedback-');
        $file = fopen($this->csvPath, 'w');
        fputcsv($file, [
            'timestamp', 'id_recepcion', 'id_factura',
            'prediccion_proveedor', 'ground_truth_proveedor', 'error_proveedor',
            'prediccion_correlativo', 'ground_truth_correlativo', 'error_correlativo',
            'prediccion_productos', 'ground_truth_productos', 'error_productos',
            'prediccion_subtotal', 'ground_truth_subtotal', 'error_subtotal',
            'prediccion_total', 'ground_truth_total', 'error_total',
            'discrepancias_corregidas', 'confianza_ocr_promedio',
        ]);
        fclose($file);

        $this->recepcion = new FeedbackRetrainingTriggerFake($this->csvPath);
    }

    protected function tearDown(): void
    {
        if (is_file($this->csvPath)) {
            unlink($this->csvPath);
        }
    }

    public function testTrainingStartsOnFifthFeedbackAndForEveryLaterFeedback(): void
    {
        for ($index = 1; $index <= 4; $index++) {
            self::assertTrue($this->recepcion->agregarFeedbackDataset([]));
            self::assertSame(0, $this->recepcion->retrainingCalls);
        }

        self::assertTrue($this->recepcion->agregarFeedbackDataset([]));
        self::assertSame(1, $this->recepcion->retrainingCalls);

        self::assertTrue($this->recepcion->agregarFeedbackDataset([]));
        self::assertSame(2, $this->recepcion->retrainingCalls);
    }
}

final class FeedbackRetrainingTriggerFake extends Recepcion
{
    public $retrainingCalls = 0;
    private $feedbackPath;

    public function __construct($feedbackPath)
    {
        $this->feedbackPath = $feedbackPath;
    }

    protected function rutaFeedbackDataset()
    {
        return $this->feedbackPath;
    }

    protected function reentrenarModelo()
    {
        $this->retrainingCalls++;
        return true;
    }
}
