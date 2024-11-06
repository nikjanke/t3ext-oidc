<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Causal\Oidc\Utility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class ConfigurationUtility
{
    /**
     * This loads the configuration from the extension and the site and merges them.
     * The site settings take precedence to overwrite specific settings.
     *
     * @param ServerRequestInterface|null $request
     * @return array
     */
    public static function getConfigurationForOidc(ServerRequestInterface $request = null): array
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        // try globals request object, when request is null
        $request = $request ?? $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
        if ($siteFinder) {
            foreach ($siteFinder->getAllSites() as $site) {
                if ($request && $request->getUri() && $request->getUri()->getHost() === $site->getBase()->getHost()) {
                    $siteConfig = $site->getConfiguration();
                }
            }

            if ($siteConfig['settings']['oidc']) {
                $siteOidcSettings = $siteConfig['settings']['oidc'];
            }
            $extensionSettings = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('oidc') ?? [];
            if ($siteOidcSettings) {
                $config = array_merge($extensionSettings, $siteOidcSettings);
            } else {
                $config = $extensionSettings;
            }
        }
        return $config;
    }
}
