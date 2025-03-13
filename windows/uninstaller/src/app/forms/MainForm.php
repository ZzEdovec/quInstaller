<?php
namespace app\forms;

use Throwable;
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
        $this->subheader->font = UXFont::load(ResourceStream::of('res://.theme/fonts/Unbounded-Regular.ttf'),13);
        
        $this->title = $GLOBALS['AppParams']['AppName'].' | '.$this->title;
        
        $this->container->content = new UXVBox;
        $this->container->content->spacing = 5;
        
        if ($GLOBALS['AppParams']['AppCustomDirs'] == null)
            $this->panel->enabled = false;
        else
        {
            foreach ($GLOBALS['AppParams']['AppCustomDirs'] as $dir)
            {
                $checkbox = new UXCheckbox;
                
                $dir = Windows::expandEnv($dir);
                $checkbox->text = $dir;
                $checkbox->tooltipText = $dir;
                $checkbox->textColor = '#f2f2f2';
                $checkbox->selected = true;
                $checkbox->cursor = 'HAND';
                
                $this->container->content->add($checkbox);
            }
        }
        
        
        
        $this->header->text = sprintf(Localization::getByCode('mainform.header'),$GLOBALS['AppParams']['AppName']);
        $this->subheader->text = Localization::getByCode('mainform.subheader');
        $this->labelAlt->text = Localization::getByCode('mainform.paths.label');
        $this->button->text = Localization::getByCode('mainform.uninstallbtn');
    }

    /**
     * @event button.action 
     */
    function doButtonAction(UXEvent $e = null)
    {    
        $this->panel->enabled = true;
        $this->button->enabled = false;
        $this->button->text = Localization::getByCode('mainform.uninstallbtn.inprogress');
        
        Animation::fadeOut($this->labelAlt,1000);
        Animation::fadeOut($this->container,1000,function ()
        {
            Animation::fadeIn($this->progressBar,300,[$this,'uninstall']);
        });
    }

    function uninstall()
    {
        $workDir = file_get_contents('workDir');
        
        new Thread(function () use ($workDir)
        {
            fs::clean($workDir);
            fs::delete($workDir);
            
            fs::delete($GLOBALS['AppParams']['InstalledAsRoot'] ? Windows::getSystemDrive().':\Users\Public\Desktop\\'.$GLOBALS['AppParams']['AppName'].'.lnk'
                                                                : Windows::expandEnv(Registry::of('HKEY_CURRENT_USER\Software\Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders')->read('Desktop')).'\\'.$GLOBALS['AppParams']['AppName'].'.lnk');
            fs::delete($GLOBALS['AppParams']['InstalledAsRoot'] ? Windows::getSystemDrive().':\ProgramData\Microsoft\Windows\Start Menu\Programs\\'.$GLOBALS['AppParams']['AppName'].'.lnk'
                                                                : System::getEnv()['APPDATA'].'\Microsoft\Windows\Start Menu\Programs\\'.$GLOBALS['AppParams']['AppName'].'.lnk');
            
            uiLater(function (){$this->progressBar->progress = 25;});
        
            if ($GLOBALS['AppParams']['RegCreated'])
            {
                if ($GLOBALS['AppParams']['InstalledAsRoot'])
                {
                    try {
                        Registry::of('HKEY_LOCAL_MACHINE\Software\Microsoft\Windows\CurrentVersion\Uninstall\\'.$GLOBALS['AppParams']['AppName'])->delete();
                    } catch (Throwable $ex) {}
                }
                else 
                {
                    try {
                        Registry::of('HKEY_CURRENT_USER\Software\Microsoft\Windows\CurrentVersion\Uninstall\\'.$GLOBALS['AppParams']['AppName'])->delete();
                    } catch (Throwable $ex) {}
                }
            }
            
            uiLater(function (){$this->progressBar->progress += 25;});
        
            if ($this->container->content->children->isEmpty() == false)
            {
                $total = $this->container->content->children->count();
                foreach ($this->container->content->children->toArray() as $count => $checkbox)
                {
                    if ($checkbox->selected)
                    {
                        fs::clean($checkbox->text);
                        fs::delete($checkbox->text);
                    }
                    
                   $progress = ($count / $total) * 100;
                   uiLaterAndWait(function () use ($progress){$this->progressBar->progress = 50 + ($progress * 50 / 100);});
                }
            }
            
            uiLater(function ()
            {
                $this->progressBar->progress = 100;
                
                $script = "@echo off\ntimeout /t 3 /nobreak >nul\nrmdir /s /q \"".fs::abs('.\\').'"';
                $scriptPath = System::getProperty('java.io.tmpdir').'clear.bat';
                file_put_contents($scriptPath,$script);
                
                UXDialog::showAndWait(Localization::getByCode('mainform.success'));
                new Process(['cmd','/c',System::getProperty('java.io.tmpdir').'clear.bat'])->start();
                App::shutdown();
            });
        })->start();
    }
}
