<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;

class PdfService
{
    private $dompdf;
    private $twig;
    private $params;

    public function __construct(Environment $twig, ParameterBagInterface $params)
    {
        $this->twig = $twig;
        $this->params = $params;

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $this->dompdf = new Dompdf($options);
    }

    /**
     * @param string $templatePath The twig template path
     * @param array $parameters Parameters for the template
     * @param string $paperSize A4, letter, etc.
     * @param string $orientation portrait or landscape
     * @return string The PDF binary output
     */
    public function generatePdf(string $templatePath, array $parameters = [], string $paperSize = 'A4', string $orientation = 'portrait'): string
    {
        // Add default parameters if needed (like logo path)
        if (!isset($parameters['logo_path'])) {
            $logoPath = $this->params->get('kernel.project_dir') . '/public/logo-manos-phone.png';
            if (file_exists($logoPath)) {
                $type = pathinfo($logoPath, PATHINFO_EXTENSION);
                $data = file_get_contents($logoPath);
                $parameters['logo_path'] = 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }


        $html = $this->twig->render($templatePath, $parameters);

        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper($paperSize, $orientation);
        $this->dompdf->render();

        return $this->dompdf->output();
    }
}
