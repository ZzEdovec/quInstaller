<?php
namespace app\forms;

use localization;
use facade\Json;
use php\compress\ZipFile;
use windows;
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
        
        if ($GLOBALS['AppParams']['AppUsesRoot'])
            $this->installPath->text = Windows::getSystemDrive().':\Program Files\\'.$GLOBALS['AppParams']['AppName'];
        else 
            $this->installPath->text = System::getEnv()['APPDATA'].'\\'.$GLOBALS['AppParams']['AppName'];
        
        
        
        
        $this->header->text = sprintf(__('mainform.header.header',$GLOBALS['Locale']), $GLOBALS['AppParams']['AppName']);
        $this->subheader->text = __('mainform.header.subheader',$GLOBALS['Locale']);
        
        $this->label->text = __('mainform.path.label',$GLOBALS['Locale']);
        
        $this->labelAlt->text = __('mainform.links.label',$GLOBALS['Locale']);
        $this->desktopLink->text = __('mainform.links.desktop',$GLOBALS['Locale']);
        $this->appmenuLink->text = __('mainform.links.appmenu',$GLOBALS['Locale']);
        
        $this->label3->text = __('mainform.system.label',$GLOBALS['Locale']);
        $this->uninstaller->text = __('mainform.system.uninstaller',$GLOBALS['Locale']);
        $this->uninstallerReg->text = __('mainform.system.reguninstaller',$GLOBALS['Locale']);
        
        $this->button->text = __('mainform.installbutton',$GLOBALS['Locale']);
        
        $this->progressLabel->text = __('mainform.installation.label',$GLOBALS['Locale']);
        $this->runButton->text = __('mainform.installation.run',$GLOBALS['Locale']);
        $this->exitButton->text = __('mainform.installation.exit',$GLOBALS['Locale']);
    }

    /**
     * @event uninstaller.click 
     */
    function doUninstallerClick(UXMouseEvent $e = null)
    {    
        if ($this->uninstaller->selected == false)
        {
            if ($this->uninstallerReg->selected)
            {
                $this->uninstallerReg->data('tempUnchecked',true);
                $this->uninstallerReg->selected = false;
            }
            
            $this->uninstallerReg->enabled = false;
        }
        elseif ($this->uninstaller->selected)
        {
            if ($this->uninstallerReg->data('tempUnchecked'))
            {
                $this->uninstallerReg->data('tempUnchecked',false);
                $this->uninstallerReg->selected = true;
            }
            
            $this->uninstallerReg->enabled = true;
        }
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
                $dir .= '\\'.$GLOBALS['AppParams']['AppName'];
            
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
        $desktopPath = Windows::isAdmin() ? Windows::getSystemDrive().':\Users\Public\Desktop'
                                          : Windows::expandEnv(Registry::of('HKEY_CURRENT_USER\Software\Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders')->read('Desktop'));
        $appmenuPath = Windows::isAdmin() ? Windows::getSystemDrive().':\ProgramData\Microsoft\Windows\Start Menu\Programs'
                                          : System::getEnv()['APPDATA'].'\Microsoft\Windows\Start Menu\Programs';
        
        fs::makeDir($this->installPath->text);
        
        $package = new ZipFile('package.zip');
        new Thread(function () use ($desktopPath,$appmenuPath,$package)
        {
            $package->unpack($this->installPath->text);
            uiLater(function (){$this->progressBar->progress = 30;});
            
            foreach (fs::scan('./jre',['excludeDirs'=>true]) as $file)
            {
                $distFile = str::replace($file,'.'.fs::separator(),$this->installPath->text.'/');
                
                fs::ensureParent($distFile);
                fs::copy($file,$distFile);
            }
            
            uiLater(function (){$this->progressBar->progress += 20;});
            
            if ($this->desktopLink->selected)
            {
                Windows::createShortcut($desktopPath.'\\'.$GLOBALS['AppParams']['AppName'].'.lnk',$this->installPath->text.'\\'.$GLOBALS['AppParams']['AppExec']);
                uiLater(function (){$this->progressBar->progress += 10;});
            }
            if ($this->appmenuLink->selected)
            {
                Windows::createShortcut($appmenuPath.'\\'.$GLOBALS['AppParams']['AppName'].'.lnk',$this->installPath->text.'\\'.$GLOBALS['AppParams']['AppExec']);
                uiLater(function (){$this->progressBar->progress += 10;});
            }
            
            if ($this->uninstaller->selected)
            {
                $uninstallerConfig = ['AppName'=>$GLOBALS['AppParams']['AppName'],'InstalledAsRoot'=>Windows::isAdmin(),'RegCreated'=>$this->uninstallerReg->selected];
                if (fs::isFile('uninstallercfg.json'))
                {
                    $customDirs = Json::decode(file_get_contents('uninstallercfg.json'));
                    $uninstallerConfig = array_merge($uninstallerConfig,$customDirs);
                }
                
                file_put_contents($this->installPath->text.'\\uninstallercfg.json',Json::encode($uninstallerConfig));
                fs::copy(ResourceStream::of('res://unins.exe'),$this->installPath->text.'\unins.exe');
                uiLater(function (){$this->progressBar->progress += 10;});
                
                if ($this->uninstallerReg->selected)
                {
                    $reg = Registry::of(Windows::isAdmin() ? 'HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\Rudi'
                                                           : 'HKEY_CURRENT_USER\Software\Microsoft\Windows\CurrentVersion\Uninstall\Rudi');
                                                           
                    $reg->create();
                    $reg->add('DisplayIcon',$this->installPath->text.'\\'.$GLOBALS['AppParams']['AppExec']);
                    $reg->add('DisplayName',$GLOBALS['AppParams']['AppName']);
                    $reg->add('Publisher',$GLOBALS['AppParams']['Publisher']);
                    $reg->add('InstallDate',Time::now()->toString('yyyyMMdd'));
                    $reg->add('InstallLocation',fs::abs('.\\'));
                    $reg->add('NoModify',1,'REG_DWORD');
                    $reg->add('NoRepair',1,'REG_DWORD');
                    $reg->add('UninstallString',$this->installPath->text.'\unins.exe');
                    
                    uiLater(function (){$this->progressBar->progress += 10;});
                }
            }
            
            uiLater(function (){$this->endInstall();});
        })->start();
    }
    
    function endInstall()
    {
        $this->progressBar->progress = 100;
        $this->progressLabel->text = __('mainform.installation.label.success',$GLOBALS['Locale']);;
        
        Animation::fadeOut($this->progressBar,1000,function ()
        {
            Animation::fadeIn($this->exitButton,300);
            Animation::fadeIn($this->runButton,300);
            
            $this->runButton->on('click',function ()
            {
                execute($this->installPath->text.'/'.$GLOBALS['AppParams']['AppExec']);
                App::shutdown();
            });
        });
    }

}
