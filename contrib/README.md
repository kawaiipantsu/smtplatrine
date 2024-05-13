# ᵔᴥᵔ SMTPLATRINE CONTRIBS

> **THIS DIRECTORY CONTAINS SMALL CONTRIB TO EASE THE USE OF SMTPLATRINE**

```
📦contrib
 ┣ 📜.multitailrc
 ┣ 📜monitor_git_graph.sh
 ┣ 📜monitor_listner.sh
 ┣ 📜README.md
 ┣ 📜smtplatrine.service
 ┗ 📜telnet_smtp_example.txt
 ```

## Files

| Filename | Description |
|------:|:------|
| [.multitailrc](.multitailrc) | This is a file for MultiTail with smtllatrine color scheme :)
| [monitor_git_graph.sh](monitor_git_graph.sh) | Script to display GIT Graph visualizing your work while working! |
| [monitor_listner.sh](monitor_listner.sh) | Script to help you debug networking if the server/clients work |
| [README.md](README.md) | This file duh! |
| [smtplatrine.service](smtplatrine.service) | Systemd service file for smtplatrine |
| [telnet_smtp_example.txt](telnet_smtp_example.txt) | Telnet/SMTP command step by step for sending a mail for reference|


## Systemd - Setup smtplatrine step by step

I use systemd, you might not - I do.  
What ever way floats your boat you should go with that. If you use systemd to then you're in luck, as i have prepared it all. Enjoy!

### Installation steps

1) **Copy the systemd service file to it's right place:**  
`sudo cp contrib/smtplatrine.service /etc/systemd/system/`

2) **Reload the daemon to catch new service file:**  
`sudo systemctl daemon-reload`

3) **Done! Run with it "as-is" and do start / stop manually:**  
`sudo systemctl start smtplatrine.service`  
`sudo systemctl stop smtplatrine.service`

4) *(Optional)* **Enable service to automatically start on boot/startup:**  
`sudo systemctl enable smtplatrine.service`

### Check to see if it's running

To see if it's running okay you can do `sudo systemctl status smtplatrine.service` and you should get an output similar to this. If you do not, then you need to look at logs and debug the problem yourself. In most cases it's typically file permissions that are wrong. Try doing `sudo chmod 755 smtplatrine` on the main file.

**systemd - status of a running smtplatrine**
```yml
● smtplatrine.service - SMTPLATRINE - A SMTP Honeypot
     Loaded: loaded (/etc/systemd/system/smtplatrine.service; disabled; preset: enabled)
     Active: active (running) since Mon 2024-05-13 08:54:04 CEST; 35s ago
   Main PID: 194210 (php)
      Tasks: 1 (limit: 9475)
     Memory: 9.9M
        CPU: 30ms
     CGroup: /system.slice/smtplatrine.service
             └─194210 smtplatrine

May 13 08:54:04 web systemd[1]: Started smtplatrine.service - SMTPLATRINE - A SMTP Honeypot.
```


## Other random notes

These notes are just me dotting down random things i need to remember or that i believe i might need in the future but really don't want to remember :)

Or it might just be what i think is a good idea at the time and therefore want to make sure i don't forget it :D

### Graphical/Layout contraints
**Logo colors:**

- `#411900`
- `#6E1005`

**Good background color?**

- `#021B2A`
