<?php

declare(strict_types=1);

namespace MarceloFecchio\Correios\Etiquetas;

/**
 * Converte o ZPL gerado pelo componente em PDF, como alternativa ao rótulo oficial (`$correios->rotulo()`).
 *
 * O componente traz `ConversorLabelary`. Para usar outro serviço (ou um renderizador local), implemente esta
 * interface e injete-a no lugar.
 */
interface ConversorPdf
{
    /**
     * Todas as etiquetas da `EtiquetaZpl` saem num único PDF.
     *
     * @throws \MarceloFecchio\Correios\Excecoes\CorreiosException
     */
    public function converter(EtiquetaZpl $etiqueta): EtiquetaPdf;
}
