# samba部分

编辑人：Rannnn

### 任务描述：

请采用samba服务，实现资源共享。

（1）在linux3上创建user00-user19等20个用户；user00和 user01添加到manager组，user02和user03添加到dev组。把用户 user00-user03添加到samba用户。
（2）配置linux3为samba服务器,建立共享目录/srv/sharesmb， 共享名与目录名相同。manager组用户对sharesmb共享有读写权限， dev组对sharesmb共享有只读权限；用户对自己新建的文件有完全权限，对其他用户的文件只有读权限，且不能删除别人的文件。在本机 用smbclient命令测试。
（3）在linux4修改/etc/fstab,使用用户user00实现自动挂载 linux3的sharesmb共享到/sharesmb。 

### 环境描述：

VMware虚拟环境

IP分配：
Linux：192.168.152.101、102、103、104
Windows：192.168.152.250

### 解题部分：

首先先安装samba

```
yum install -y samba*
```

建立用户和组

```
for i in {00..19};do useradd user$i;done
for i in {dev,sale};do groupadd $i;done
```

将用户拉入用户组

```
for i in {00..01};do usermod -g dev user$i;done
for i in {02..03};do usermod -g sale user$i;done
```

给用户加上密码

```
for i in {00..03};do echo -e "Pass-1234\nPass-1234" | pdbedit -a user$i -t;done
```

创建文件夹/srv/sharesmb作为共享目录

```
mkdir /srv/sharesmb
```

给文件夹775权限和a+s权限

```
chmod 775 /srv/sharesmb/
chmod a+s /srv/sharesmb/
```

更改/srv/sharesmb文件夹的acl

```
setfacl -m g:dev:rwx /srv/sharesmb/
setfacl -m g:sale:rx /srv/sharesmb/
```

编辑/etc/samba/smb.conf文件，在文件末尾添加以下内容

```
[sharesmb]
	path = /srv/sharesmb
	valib users = @dev,@sale
	write list = @dev
	writeable = yes
	create mask = 6775
	directory mask = 6775
```

保存退出，将smb服务加入自启动，并重新启动服务

```
systemctl enable smb.service --now
```

开放139、445端口

```
firewall-cmd --add-port={139,445}/tcp
firewall-cmd --add-port={139,445}/tcp --per
```

更改SELinux策略，将samba_export_all_rw状态更改为on

```
setsebool -P samba_export_all_rw on
```

开始验证

```
cd ~
ls
smbclient -c "put ansible.txt" //192.168.152.103/sharesmb -U user00%Pass-1234
```

如果出现下面的提示，说明服务搭建成功

```
putting file ansible.txt as \ansible.txt (0.0 kb/s) (average 0.0 kb/s)
```

到/srv/sharesmb/目录下查看是否有ansible.txt文件

```
[root@linux3 ~]# cd /srv/sharesmb/
[root@linux3 sharesmb]# ls
ansible.txt
```

因为user00属于manager组，且manager组用户对于共享目录为可读写权限，所以我们再尝试使用user02来上传和删除文件

```
smbclient -c "put ansible.txt" //192.168.152.103/sharesmb -U user02%Pass-1234
smbclient -c "rm ansible.txt" //192.168.152.103/sharesmb -U user02%Pass-1234
```

看到下面的提示说明acl控制访问正常

```
NT_STATUS_ACCESS_DENIED opening remote file \ansible.txt
NT_STATUS_ACCESS_DENIED deleting remote file \ansible.txt
```

验证成功，服务正常

在Linux4上安装samba服务和cifs-utils服务

```
yum install -y samba* cifs-utils
```

编辑/etc/fstab文件，在文件底部输入以下内容，实现自动挂载

```
//linux3.skills.lan/sharesmb /sharesmb cifs _netdev,username=user00,password=Pass-1234 0 0
```

建立/sharesmb目录

```
mkdir /sharesmb
```

进行挂载

```
mount -a
```

用df命令进行查看是否挂载成功

```
df -Th
```

出现了以下字段就说明成功

```
//linux3.skills.lan/sharesmb	cifs	35G		13G		23G		36%	/sharesmb
```

创建一个文件测试一下

```
[root@linux4 ~]# mkdir /sharsmb/test
[root@linux4 ~]# cd /sharesmb/
[root@linux4 sharesmb]# ls
ansible.txt test
```

测试成功

至此，samba部分完成

