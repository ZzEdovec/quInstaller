# quInstaller
Simple installer for you program. No compilation needed. (README in-progress)

### What is it?
quInstaller - a universal and simple installer for any program in any programming language. It does not require compilation every time you want to create a new installer. It also supports applications that use Java Runtime Environment (JRE) versions 8 and later due to the fact that it runs on the JVM itself!

### Please donate
My projects are created out of pure enthusiasm, and if you appreciate my work, you can financially support me.
[Click here to donate](https://www.donationalerts.com/r/queinu)

# Creating a installer
Let's create our first installer based on quInstaller.

1. Download the latest version from the Releases
2. Unpack it somewhere
3. Open `installercfg.json` file in any text editor
	You can configure the parameters in this file.
	`AppName` - Name of your application
	`AppUsesRoot` - Specifies whether to force the request for administrator rights (UAC)
	`AppExec` - Main executable file of your program
	`Publisher` - Author or publisher of the application
	
4. Place the logo of your program in the same folder where the `main.exe` file is located and name the file `appIcon.png`.
5. Pack all the files of your program that need to be installed into an archive named `package.zip` and place it in the same folder.
6. If your program has additional folders, for example, in `%appdata%` or `%temp%`,

