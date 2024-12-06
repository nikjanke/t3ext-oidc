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
use WIRO\WiroMaster\Utility\SettingsUtility;

abstract class ConfigurationUtilityOidc
{
    /**
     * This loads the configuration from the extension and the site and merges them.
     * You have to use the same names as in the Extensionconfiguration.
     * Example:
     * * oidc
     * * * usersStoragePid
     * The site settings take precedence to overwrite specific settings.
     *
     * @param ServerRequestInterface|null $request
     * @return array
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public static function getConfigurationForOidc(ServerRequestInterface $request = null): array
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        // try globals request object, when request is null
        $request = $request ?? $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
        $rootPageId = '';
        if ($siteFinder) {
            $siteConfig = [];
            $siteOidcSettings = [];
            foreach ($siteFinder->getAllSites() as $site) {
                if ($request && $request->getUri() && $request->getUri()->getHost() === $site->getBase()->getHost()) {
                    $rootPageId = $site->getRootPageId();
                    $siteConfig = $site->getConfiguration();
                }
            }

            if ($siteConfig && $siteConfig['settings']['oidc']) {
                $siteOidcSettings = $siteConfig['settings']['oidc'];
            }
            $extensionSettings = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('oidc') ?? [];
            if ($siteOidcSettings) {
                $config = array_merge($extensionSettings, $siteOidcSettings);
                $config['rootPageId'] = $rootPageId;
                $settings = GeneralUtility::removeDotsFromTS(SettingsUtility::getConfigurationFromExistingTsFe($rootPageId));
                $userPid = $settings['plugin']['tx_femanager']['settings']['installateureStoragePid'];
                $config['usersStoragePid'] = $userPid;
                if($siteOidcSettings['clientIdentifier']) {
                    $config['oidcClientKey'] = $GLOBALS['SSO'][$siteOidcSettings['clientIdentifier']]['CLIENT_ID'];
                    $config['oidcClientSecret'] = $GLOBALS['SSO'][$siteOidcSettings['clientIdentifier']]['CLIENT_ID'];
                }
            } else {
                $config = $extensionSettings;
            }
        }
        return $config;
    }
}
