<?php
namespace app\modules;

use windows;
use Throwable;
use facade\Json;
use std, gui, framework, app;


class AppModule extends AbstractModule
{

    /**
     * @event action 
     */
    function doAction(ScriptEvent $e = null)
    {
        $GLOBALS['AppParams'] = Json::decode(file_get_contents('uninstallercfg.json'));
        
        if ($GLOBALS['AppParams'] == null)
        {
            UXDialog::showAndWait(Localization::getByCode('APPMODULE.NOCONFIG'),'ERROR');
            App::shutdown();
            return;
        }
        if ($GLOBALS['AppParams']['InstalledAsRoot'] and Windows::isAdmin() == false)
        {
            try
            {
                Windows::requireAdmin();
            } catch (Throwable $ex)
            {
                UXDialog::showAndWait(Localization::getByCode('APPMODULE.NEEDROOT'),'ERROR');
                App::shutdown();
            }
            return;
        }
        if (str::contains(fs::normalize($GLOBALS['argv'][0]),System::getProperty('java.io.tmpdir')) == false)
        {
            $this->copyToTemp();
            return;
        }
        
        app()->showForm('MainForm');
    }
    
    function copyToTemp()
    {
        $selfPath = fs::normalize($GLOBALS['argv'][0]);
        $tmpPath = System::getProperty('java.io.tmpdir').'quUninstaller/';
        
        fs::makeDir($tmpPath);
        fs::copy($selfPath,$tmpPath.fs::name($selfPath));
        fs::copy('uninstallercfg.json',$tmpPath.'uninstallercfg.json');
        
        foreach (fs::scan('./jre',['excludeDirs'=>true]) as $file)
        {
            $fileintemp = str::replace($file,'.'.fs::separator(),$tmpPath);
            
            fs::ensureParent($fileintemp);
            fs::copy($file,$fileintemp);
        }
        
        file_put_contents($tmpPath.'workDir',fs::abs('./'));
        
        try{
            execute($tmpPath.'unins.exe');}
        catch (Throwable $ex)
        {
            UXDialog::showAndWait(sprintf(Localization::getByCode('APPMODULE.STARTFAILED'),$ex->getMessage()),'ERROR');
        }
        
        App::shutdown();
    }

}
