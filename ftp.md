# ftp服务

编辑人：Rannnn

### 任务描述：

请采用FTP服务器，实现文件安全传输。
（1）配置linux2为FTP服务器，安装vsftpd，新建本地用户test， 本地用户登陆ftp后的目录为/var/ftp/pub，可以上传下载。
（2）配置ftp虚拟用户认证模式，虚拟用户ftp1和ftp2映射为 ftp，ftp1登录ftp后的目录为/var/ftp/vdir/ftp1，可以上传下载, 禁止上传后缀名为.docx的文件；ftp2登录ftp后的目录为 /var/ftp/vdir/ftp2，仅有下载权限。
（3）使用ftp命令在本机验证。

### 解题部分：

```
yum install -y vsftpd ftp
```

编辑vsftpd.conf

```
vim /etc/vsftpd/vsftpd.conf
```

搜索chroot：

```
/chroot
```

将`chroot_local_user=YES`打开，并添加一条默认地址：

```
local_root=/var/ftp/pub
```

为了防止500报错，再添加一条：

```
allow_writeable_chroot=YES
```

新建用户：

```
useradd test
```

给用户设置密码：

```
echo "pass123" | passwd --stdin test
```

给ftp目录root权限

```
chmod 777 /var/ftp/pub
```

重启vsftpd：

```
systemctl enable vsftpd --now
service vsftpd restart
```

开放端口21：

```
firewall-cmd --add-port=21/tcp 
firewall-cmd --add-port=21/tcp --per
```

取消对ftp的安全限制：

```
setsebool -P ftpd_full_access on
```

取消端口限制：

```
setsebool -P ftpd_use_passive_mode on
```

开始测试：

```
cd /var/ftp/pub
ls
touch 1.txt
cd ~
ls
ftp -n 192.168.152.102
user test pass123
ls
put ansible.txt
ls
get 1.txt
quit
```

查看本地root下是否有1.txt

接下来编辑/etc/vsftpd/vsftpd.conf

在最下面加入以下代码：

```
guest_enable=YES
guest_username=ftp
user_config_dir=/etc/vsftpd/vuserconf
virtual_use_local_privs=YES
```

转到vsftpd目录下，新建一个vuserconf文件夹，并在内新建一个ftp1文件

```
cd /etc/vsftpd
mkdir vuserconf
cd vuserconf
vim ftp1
```

输入：

```
local_root=/var/ftp/vdir/ftp1
deny_file={*.sh}
write_enable=YES
```

保存退出，编辑ftp2

输入：

```
local_root=/var/ftp/vdir/ftp2
anon_world_readable_only=YES
write_enable=NO
```

保存退出，回退到vsftpd目录下，给vuserconf文件夹root权限;

```
chmod -R 777 vuserconf
```

创建vdir文件夹：

```
mkdir /var/ftp/vdir
```

创建用户文件夹：

```
mkdir /var/ftp/vdir/{ftp1,ftp2}
```

给权限到ftp：

```
chown -R ftp:ftp /var/ftp/vdir/
```

给root权限到ftp1：

```
chmod 777 -R /var/ftp/vdir/ftp1
```

给权限到ftp2：

```
chmod -w -R /var/ftp/vdir/ftp2
```

给一个ACL用来映射用户：

```
setfacl -m u:ftp:rwx /var/ftp/vdir/ftp1
setfacl -m u:ftp:rx /var/ftp/vdir/ftp2
```

在vsftpd目录下新建一个vuser.list列表：

输入：

```
ftp1
pass123
ftp2
pass123
```

保存退出，将其生成为数据库：

```
db_load -T -t hash -f vuser.list vuser.db
```

因为vsftpd.conf的结尾已经告诉我们要使用的pem在vsftpd下，所以我们要编辑一下：

```
vim /etc/pam.d/vsftpd
```

要从上往下写（在第一行下面开始，也就是第一行按回车开始写）：

```
auth	sufficient	pam_userdb.so db=/etc/vsftpd/vuser
account	sufficient	pam_userdb.so db=/etc/vsftpd/vuser
```

重启服务：

```
systemctl restart vsftpd.service
```

开启虚拟化规则：

```
setsebool -P ftpd_connect_all_unreserved on
```

验证：

```
cd /var/ftp/vdir/ftp1
touch 1
cd ..
ls
cd ftp2
ls
chmod 2
touch 2
ls
cd ..
ls
ll
cd ~
ls
ftp -n 192.168.152.102
user ftp1 pass123
ls
put ssh.sh #会显示550报错
put ansible.txt #会正常传输
quit
```

再试一下ftp2：

```
ftp -n 192.168.152.102
user ftp2 pass123
ls
put ansible.txt #会显示550报错
put ssh.sh #会显示550报错
get 2 #会正常传输
```

到此，ftp结束。