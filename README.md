![quInstaller window](https://github.com/ZzEdovec/quInstaller/raw/refs/heads/main/readme-banner.png)
# ⬇️ quInstaller
Simple installer for you program. No compilation needed. (README in-progress)

### 🌍 Documentation language
English | [Русский](https://github.com/ZzEdovec/quInstaller/blob/main/README.ru.md)

### ❓ What is it?
quInstaller - a universal and simple installer for any program in any programming language. It does not require compilation every time you want to create a new installer. It also supports applications that use Java Runtime Environment (JRE) versions 8 and later due to the fact that it runs on the JVM itself!
### 🪲 Bugs? Questions? Ideas?
Contact me in [Telegram](https://t.me/queinu) or open an [issue](https://github.com/ZzEdovec/quInstaller/issues)

### 🥺 Please donate
My projects are created out of pure enthusiasm, and if you appreciate my work, you can financially support me.
[Click here to donate](https://www.donationalerts.com/r/queinu)

## 🚀 Creating a installer
**Let's create our first quInstaller.**

1. Download the latest version from the [Releases](https://github.com/ZzEdovec/quInstaller/releases)
2. Unpack it somewhere

### ⚙️ Configuring installer
1. Open `installercfg.json` file in any text editor.
		You can configure the installer parameters in this file.
	#### Cross-platform parameters
	- `AppName` - Name of your application **(REQUIRED)**
	- `AppExec` - Main executable file of your program on Windows (string), array of arguments on Linux (even if you specify only the executable file to run without arguments, it should still be inside the array). For Linux see example [here](https://github.com/ZzEdovec/quInstaller#-environment-variables) **(REQUIRED)**
    - `AppUsesAutoStart` - Specifies whether, by default, the user should be prompted to add the program to the startup
	#### Windows-only parameters
	- `Publisher` - Author or publisher of the application **(REQUIRED)**
	- `AppUsesRoot` - Specifies whether to force the request for administrator rights (UAC) (`false` by default)
	#### Linux-only parameters
	- `AppEnv` - an array of environment variables that must be set for the program (see example [here](https://github.com/ZzEdovec/quInstaller#-environment-variables))
	- `GenericName` - Generic name of the application, for example `Web Browser`
	
2. Place the logo of your program in the same folder where the `main.exe` (or `installer.jar` on Linux) file is located and name it `appIcon.png`.
3. Pack all the files of your program that need to be installed into an archive named `package.zip` and place it in the same folder.

### 🗑️ Configuring uninstaller
**If your program creates additional folders** outside of its directory during operation, you can specify them inside the `AppCustomDirs` array in the `uninstallercfg.json` file. In this case, when deleting the program, the user will be prompted to delete these folders.

**If your program does not create such folders**, simply delete the `uninstallercfg.json` file.

### 🌐 Environment variables
**On Windows**, you can use [environment variables](https://learn.microsoft.com/en-us/windows/deployment/usmt/usmt-recognized-environment-variables) when specifying folders in the uninstaller configuration.
For example,

> uninstallercfg.json

    {
        "AppCustomDirs":[
            "%TEMP%\\Rudi",
            "%APPDATA%\\Rudi"
        ]
    }

**On Linux**, you can only use `%TEMP%` or `%USERHOME%` variables in the **uninstaller** configuration, but unlike the Windows version, you can use the `%JAVA_BIN%` and `%APP_HOME%` variables in the `AppExec` and `AppEnv` params of **installer** configuration.

- `%JAVA_BIN%` - the path to the Java main executable file, which the installer will copy to the program installation directory
- `%APP_HOME%` - Program installation folder

For example,

> installercfg.json

    {
        "AppName":"Rudi",
        "AppExec": [
	        "%JAVA_BIN%",
	        "-jar",
	        "%APP_HOME%/Rudi.jar"
		],
		"AppEnv": [
			"GDK_BACKEND=x11",
			"JAVA_HOME=%APP_HOME%/jre"
		]
    }

### 🧪 Testing installer
After we have everything set up, run `main.exe` on Windows or `installer.jar` on Linux. 

If you don't have JRE 8 with JavaFX installed on **Linux**, use the JRE supplied with quInstaller to run it:
1. Open terminal and change dir (`cd`) to quInstaller main path
2. Execute the `jre/bin/java -jar quInstaller.jar` command

*If Java crashed with a critical error after executing the command and you are using Wayland, use the* `GDK_BACKEND=x11 jre/bin/java -jar quInstaller.jar` *command*

**Now, if the installer has started without errors, you can proceed to the next step.** However, if the installer has issued a file corruption alert, check for `package.zip`, `appIcon.png` and `installercfg.json` files. They are described  [here](https://github.com/ZzEdovec/quInstaller?tab=readme-ov-file#configuring-installer).

### 📦 Building quInstaller
#### Windows
A quick way is still in development, so.. you will need [WinRar](https://www.win-rar.com/start.html) to continue
1. In the explorer, select all files that the installer needs and click the right mouse button
2. In the context menu, select WinRAR `Add to archieve..`
3. In the window that opens, select `Create SFX archive`. You can change the archive name, format and packaging method as you wish
4. In the `Advanced` tab open `SFX options...`
5. Open `Install` and in the `Run after extraction` field, enter `main.exe`
6. In the `Modes` tab, enable `Unpack to temporary folder` and `Hide all`
7. In the `Icon` tab, you can optionally add the SFX icon, which will be the icon of your installer.
8. Now just click `Ok`, wait for the archive to be created, and that's it! **Your installer is ready.**
#### Linux
1. Install `makeself` through your distribution's package manager or from [GitHub](https://github.com/megastep/makeself)
2. Open terminal and change dir (`cd`) to quInstaller main path
3.  Now use `makeself` using the following template:

`makeself --xz ./ output_file_name "label" env GDK_BACKEND=x11 ./jre/bin/java -jar ./installer.jar`

For example,
`makeself --xz ./ rudi_installer "Rudi installer package" env GDK_BACKEND=x11 ./jre/bin/java -jar ./installer.jar`

You can see the full `makeself` documentation [here](https://github.com/megastep/makeself?tab=readme-ov-file#usage).

**And... that's all**. The installer of your program is ready. You can publish it.
