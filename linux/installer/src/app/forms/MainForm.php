<?php
namespace app\forms;

use localization;
use facade\Json;
use php\compress\ZipFile;
use std, gui, framework, app;


class MainForm extends AbstractForm
{

    /**
     * @event showing 
     */
    function doShowing(UXWindowEvent $e = null)
    {
        $this->header->font = UXFont::load(ResourceStream::of('res://.theme/fonts/Unbounded-Black.ttf'),20);
        $this->subheader->font = UXFont::load(ResourceStream::of('res://.theme/fonts/Unbounded-Regular.ttf'),15);
        
        $this->title = $GLOBALS['AppParams']['AppName'].' | '.$this->title;
        $this->appIcon->image = new UXImage('appIcon.png');

        $this->installPath->text = System::getProperty('user.home').'/.local/share/'.$GLOBALS['AppParams']['AppName'];
        
        
        
        
        $this->header->text = sprintf(__('mainform.header.header',$GLOBALS['Locale']), $GLOBALS['AppParams']['AppName']);
        $this->subheader->text = __('mainform.header.subheader',$GLOBALS['Locale']);
        
        $this->label->text = __('mainform.path.label');
        
        $this->labelAlt->text = __('mainform.links.label',$GLOBALS['Locale']);
        $this->desktopLink->text = __('mainform.links.desktop',$GLOBALS['Locale']);
        $this->appmenuLink->text = __('mainform.links.appmenu',$GLOBALS['Locale']);
        
        $this->label3->text = __('mainform.system.label',$GLOBALS['Locale']);
        $this->uninstaller->text = __('mainform.system.uninstaller',$GLOBALS['Locale']);
        
        $this->button->text = __('mainform.installbutton',$GLOBALS['Locale']);
        
        $this->progressLabel->text = __('mainform.installation.label',$GLOBALS['Locale']);
        $this->runButton->text = __('mainform.installation.run',$GLOBALS['Locale']);
        $this->exitButton->text = __('mainform.installation.exit',$GLOBALS['Locale']);
    }

    /**
     * @event button.action 
     */
    function doButtonAction(UXEvent $e = null)
    {    
        $this->panel->enabled = $this->panelAlt->enabled = $this->panel3->enabled = $this->button->enabled = false;
        $this->button->text = __('mainform.installbutton.started',$GLOBALS['Locale']);;
        
        Animation::fadeOut($this->appIcon,500,function ()
        {
            $this->appIcon->hide();
            
            Animation::fadeIn($this->panel4,500,[$this,'install']);
        });
    }

    /**
     * @event installPath.click 
     */
    function doInstallPathClick(UXMouseEvent $e = null)
    {    
        $dirChooser = new UXDirectoryChooser;
        
        $dir = $dirChooser->showDialog($this);
        if ($dir->canWrite() == false)
        {
            UXDialog::show(__('mainform.path.writeerror',$GLOBALS['Locale']),'ERROR');
            return;
        }
        if ($dir != null)
        {
            if (uiConfirm(__('mainform.path.progrootcreate',$GLOBALS['Locale'])))
                $dir .= '/'.$GLOBALS['AppParams']['AppName'];
            
            $this->installPath->text = $dir;
        }
    }

    /**
     * @event exitButton.action 
     */
    function doExitButtonAction(UXEvent $e = null)
    {    
        App::shutdown();
    }
    
    function install()
    {
        $desktopPath = str::trim(execute('xdg-user-dir DESKTOP',true)->getInput()->readFully());
        $appmenuPath = System::getProperty('user.home').'/.local/share/applications';
        $execCMD = str::replace($GLOBALS['AppParams']['AppExec'],'%JAVA_BIN%',$this->installPath->text.'/jre/bin/java');
        $execCMD = str::replace($execCMD,'%APP_PATH%',$this->installPath->text);
        $shortcutContent = "[Desktop Entry]\n".
                           "Name=".$GLOBALS['AppParams']['AppName']."\n".
                           "GenericName=".$GLOBALS['AppParams']['GenericName']."\n".
                           "Exec=".$execCMD."\n".
                           "Icon=".$this->installPath->text."/quInstaller/appIcon.png\n".
                           "Path=".$this->installPath->text."\n".
                           "Type=Application";
        
        fs::makeDir($this->installPath->text);
        
        $package = new ZipFile('package.zip');
        new Thread(function () use ($desktopPath,$appmenuPath,$package,$shortcutContent,$execCMD)
        {
            $package->unpack($this->installPath->text);
            uiLater(function (){$this->progressBar->progress = 30;});
            
            foreach (fs::scan('./jre',['excludeDirs'=>true]) as $file)
            {
                $distFile = str::replace($file,'.'.fs::separator(),$this->installPath->text.'/');
                
                fs::ensureParent($distFile);
                fs::copy($file,$distFile);
            }
            new Process(['chmod','+x',$this->installPath->text.'/jre/bin/java'])->start();
            
            uiLater(function (){$this->progressBar->progress += 20;});
            
            if ($this->desktopLink->selected)
            {
                file_put_contents($desktopPath.'/'.$GLOBALS['AppParams']['AppName'].'.desktop',$shortcutContent);
                new Process(['chmod','+x','/home/queinu/Рабочий стол/Rudi.desktop'])->start();
                uiLater(function (){$this->progressBar->progress += 10;});
            }
            if ($this->appmenuLink->selected)
            {
                file_put_contents($appmenuPath.'/'.$GLOBALS['AppParams']['AppName'].'.desktop',$shortcutContent);
                uiLater(function (){$this->progressBar->progress += 10;});
            }
            
            if ($this->uninstaller->selected)
            {
                $uninstallerConfig = ['AppName'=>$GLOBALS['AppParams']['AppName']];
                if (fs::isFile('uninstallercfg.json'))
                {
                    $customDirs = Json::decode(file_get_contents('uninstallercfg.json'));
                    $uninstallerConfig = array_merge($uninstallerConfig,$customDirs);
                }
                
                file_put_contents($this->installPath->text.'/uninstallercfg.json',Json::encode($uninstallerConfig));
                fs::copy(ResourceStream::of('res://uninstaller.jar'),$this->installPath->text.'/uninstaller.jar');
                uiLater(function (){$this->progressBar->progress += 10;});
            }
            
            fs::makeDir($this->installPath->text.'/quInstaller');
            fs::copy('appIcon.png',$this->installPath->text.'/quInstaller/appIcon.png');
            
            uiLater(function () use ($execCMD){$this->endInstall($execCMD);});
        })->start();
    }
    
    function endInstall($execCMD)
    {
        $this->progressBar->progress = 100;
        $this->progressLabel->text = __('mainform.installation.label.success',$GLOBALS['Locale']);;
        
        Animation::fadeOut($this->progressBar,1000,function () use ($execCMD)
        {
            Animation::fadeIn($this->exitButton,300);
            Animation::fadeIn($this->runButton,300);
            
            $this->runButton->on('click',function () use ($execCMD)
            {
                execute($execCMD);
                App::shutdown();
            });
        });
    }

}
