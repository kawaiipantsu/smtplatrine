# smtplatrine log files

This is the logging directory for smtplatrine. You can either log here by setting the **logger_destination** to `"file"`or `"both"`. If the files are not there they will be created if it's able to do so. Please make sure permissions are correct or else it will fail.

```shell
$ chown <user>:<group> logs
$ chmod 755 logs
```

It will also try to create the directory if it's not there, but this is not sure to work. Again all depends on permissions :)

## smtplatrine.log

Main log file, all output will be stored here. This should be the best approch to see what going on overall. But on very busy servers this might be to fast to follow. Especially if debug is also enabled.

**Log format**
```log
2024-05-10 20:09:12 smtplatrine[1234]: (INFO) Started SMTPLATRINE - THUGSred SMTP Honeypot
2024-05-10 20:09:12 smtplatrine[1234]: (WARN) Child quitted unexpected
2024-05-10 20:09:12 smtplatrine[1234]: (ERROR) FATAL Could not bind to socket!
2024-05-10 20:09:12 smtplatrine[1234]: (DEBUG) Last SMTP command recieved: EHLO
```
- YYYY-MM-DD HH:mm:ss
- PID Title
- [PID]
- (Severity)
- Message

## smtplatrine_error.log - `ERROR`

Error severity is common and not always fatal btw, if you log that type of severity then this log will also be populated with the message. You can use this log to only view what's going wrong on a very busy server.

**Log format**
```log
2024-05-10 20:09:12 smtplatrine[1234]: FATAL Could not bind to socket!
```
- YYYY-MM-DD HH:mm:ss
- PID Title
- [PID]
- Message

## smtplatrine_debug.log - `DEBUG`

Don't really know why i have this seperated out as well. Perhaps in the future i will add it so that you can say that debug logs should not enter the defult/main log but you can still enable debug etc. I think that would be nice.

**Log format**
```log
2024-05-10 20:09:12 smtplatrine[1234]: Last SMTP command recieved: EHLO
```
- YYYY-MM-DD HH:mm:ss
- PID Title
- [PID]
- Message