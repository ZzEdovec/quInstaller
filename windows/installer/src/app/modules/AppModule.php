<?php
namespace app\modules;

use Throwable;
use windows;
use facade\Json;
use std, gui, framework, app;


class AppModule extends AbstractModule
{

    /**
     * @event action 
     */
    function doAction(ScriptEvent $e = null)
    {    
        $GLOBALS['AppParams'] = Json::decode(file_get_contents('installercfg.json'));
        $GLOBALS['Locale'] = Locale::getDefault()->getLanguage();
        if (fs::isFile('lang/'.$GLOBALS['Locale']) == false)
            $GLOBALS['Locale'] = 'en';
        
        if ($GLOBALS['AppParams'] == null or fs::isFile('package.zip') == false or fs::isFile('appIcon.png') == false)
        {
            UXDialog::showAndWait(__('appmodule.corrupted',$GLOBALS['Locale']),'ERROR');
            App::shutdown();
            return;
        }
        if (Windows::isAdmin() == false and $GLOBALS['AppParams']['AppUsesRoot'])
        {
            try
            {
                Windows::requireAdmin();
            } catch (Throwable $ex)
            {
                UXDialog::showAndWait(__('appmodule.needroot',$GLOBALS['Locale']),'ERROR');
                App::shutdown();
            }
            return;
        }
        
        app()->showForm('MainForm');
    }


}
