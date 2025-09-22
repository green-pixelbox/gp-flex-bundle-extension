<?php

declare(strict_types=1);

/*
 * This file is part of the Contao Flex Bundle Extension - Extension of tdoescher/flex-bundle.
 *
 * (c) www.green-pixelbox.de
 *
 * @license LGPL-3.0-or-later
 */

namespace GreenPixelbox\GpFlexBundleExtension;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class GpFlexBundleExtensionBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');
//        $container->parameters()->set('contao_grid_container.classes', $config['classes']);
    }
}
