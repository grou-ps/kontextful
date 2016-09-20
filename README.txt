backend1 ve backend2’de
rabbitmqctl list_queues
rabbitmqctl stop_app 
rabbitmqctl reset # Be sure you really want to do this! 
rabbitmqctl start_app
service rabbitmq-server restart

Make sure:
root@backend2:~# mkdir /var/lock/kontextfuld
root@backend2:~# chmod 777  /var/lock/kontextfuld

Make sure:
ps aux | grep kontextfuld | awk '{print $2}' | xargs kill -9


backend2’de
root@backend2:~# rabbitmqctl join_cluster rabbit@backend1
make sure
root@backend1:~# cat /var/lib/rabbitmq/.erlang.cookie
equal to
root@backend2:~# cat /var/lib/rabbitmq/.erlang.cookie

db1’de
root@db1:~# redis-cli 
127.0.0.1:6379> smembers jobs
1) "10102157364860385-facebook-20151204163847d4ef8b026c9a36acc7301abd29beb54f"
127.0.0.1:6379> srem jobs "10102157364860385-facebook-20151204163847d4ef8b026c9a36acc7301abd29beb54f"


frontend1’de
root@frontend1:/www/releases/current/test# php ping_queue.php 10102157364860385 CAAIgZAui93ZAsBAGtVmiUyJo4ZCQ4ymAsjhuIghHJbSKqPkOcbL1SomT384DmNs1Hnwd39fqAsqZBCovVkYNN1RPmHaJhZCMeJLZC3LwTiCQabKXrgUTHD10L5G6oNZA5EB0msNZAPi2fsVEy2aXFIMv824hZB0NU2la05MLEX5a6AuZCsLgifgpMa

To see the list of sign ups
cat /tmp/signups.txt 

sshfs -o idmap=www-data root@backend1:/var/log/kontextfuld /var/log/kontextfuld


