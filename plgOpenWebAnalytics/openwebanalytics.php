<?php
/**
 * This file is part of the Open-Web-Analytics JoomlaPlugin project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author     $Author$ <$Mail$>
 * @copyright  2020 $Copyright$
 * @version    $Id$
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

/**
 * Class plgSystemOpenWebAnalytics
 *
 * @author     $Author$ <$Mail$>
 * @copyright  2020 $Copyright$
 * @version    $Id$
 */
class plgSystemOpenWebAnalytics extends JPlugin
{
    private $_owa;

    /**
     * plgSystemOpenWebAnalytics constructor.
     * @param $subject
     * @param $param
     */
    public function __construct(&$subject, $param )
    {
        parent::__construct($subject, $param);

        if (!$this->owaIsInstalled()) {
            return;
        }

        $this->_owa = new owa_php();

        foreach (owa_coreAPI::getSitesList() as $site) {
            $siteDomain = $site['domain'];

            if (substr($site['domain'], -1) !== '/') {
                $siteDomain .= '/';
            }

            if ($siteDomain !== JURI::base()) {
                continue;
            }

            $this->_owa->setSiteId($site['site_id']);
            break;
        }
    }

    public function onBeforeCompileHead()
    {
        $app = JFactory::getApplication();

        if ($app->isAdmin() || !$this->owaIsInstalled() || $this->_owa->getSiteId() === null) {
            return;
        }

        $document = JFactory::getDocument();
        $document->addScriptDeclaration(
            sprintf('var owa_baseUrl="%s",owa_cmds=owa_cmds||[];owa_cmds.push(["setSiteId","%s"]),owa_cmds.push(["trackClicks"]),owa_cmds.push(["trackDomStream"]),function(){var a=document.createElement("script");a.type="text/javascript",a.async=!0,owa_baseUrl="https:"==document.location.protocol?window.owa_baseSecUrl||owa_baseUrl.replace(/http:/,"https:"):owa_baseUrl,a.src=owa_baseUrl+"modules/base/js/owa.tracker-combined-min.js";var e=document.getElementsByTagName("script")[0];e.parentNode.insertBefore(a,e)}();', $this->_owa->getSetting('base', 'public_url'), $this->_owa->getSiteId())
        );
    }

    public function onAfterRender()
    {
        $app = JFactory::getApplication();

        if ($app->isAdmin() || !$this->owaIsInstalled() || $this->_owa->getSiteId() === null) {
            return true;
        }

        if (isset($_SERVER['GEOIP_COUNTRY_NAME'])) {
            $this->_owa->setProperty('country', $_SERVER['GEOIP_COUNTRY_NAME']);
        }

        if (isset($_SERVER['GEOIP_COUNTRY_CODE'])) {
            $this->_owa->setProperty('country_code', $_SERVER['GEOIP_COUNTRY_CODE']);
        }

        if (isset($_SERVER['GEOIP_CITY'])) {
            $this->_owa->setProperty('city', $_SERVER['GEOIP_CITY']);
        }

        if (isset($_SERVER['GEOIP_REGION_NAME'])) {
            $this->_owa->setProperty('state', $_SERVER['GEOIP_REGION_NAME']);
        }

        if (isset($_SERVER['GEOIP_LATITUDE'])) {
            $this->_owa->setProperty('latitude', $_SERVER['GEOIP_LATITUDE']);
        }

        if (isset($_SERVER['GEOIP_LONGITUDE'])) {
            $this->_owa->setProperty('longitude', $_SERVER['GEOIP_LONGITUDE']);
        }

        if (owa_coreAPI::getCurrentUrl() === '') {
            $this->_owa->setProperty('page_url', '/');
        }

        $this->_owa->setPageTitle($app->getMenu()->getActive()->title);
        $this->_owa->trackPageView();

        return true;
    }

    private function owaIsInstalled()
    {
        $path = $this->params->get('path', '');

        $owaPhp = JPATH_ROOT . '/' . $path . '/owa_php.php';
        $owaCoreApi = JPATH_ROOT . '/' . $path . '/owa_coreAPI.php';

        if (!file_exists($owaPhp) || !file_exists($owaCoreApi)) {
            return false;
        }

        require_once ($owaPhp);
        require_once ($owaCoreApi);

        return true;
    }
}