# ActiveCollab Task Form

This project lets you create a form that can send tasks to ActiveCollab. Great if you need to collect support requests, requests for proposal etc.

## Development

### Task Form Command Line Utility

Part of this project is a handy command line utility that you can use to connect to your ActiveCollab. In order to use it, `cd` to directory of the form project using your Terminal (Command Prompt on Windows, Terminal on Mac etc), and run this:

    php bin/tasks-form.php -V
    
You should get output like this:

    Active Collab TaskForm 1.0.0
    
If not, your development computer may not have PHP installed on it, you may be in an incorrect directory or something similar.

### Conneting to ActiveCollab

Connecting the form to ActiveCollab requires that you run one command, and follow through the steps. Which exact command depends of your whether you are using ActiveCollab on our Cloud platform, or you can self-host it. 

### Using PHP built in server

One of the easy ways to test how everything's working is to use PHP's built in web server during development. To launch the server, just run:

    php -S localhost:8000
    
System will report that server is running, and you will be able to access your form when you visit http://localhost:8000 (if you have created `index.html``) or http://localhost:8000/example.html if you want to see example form. 

Instructions and fully functional example coming soon. ⏳
