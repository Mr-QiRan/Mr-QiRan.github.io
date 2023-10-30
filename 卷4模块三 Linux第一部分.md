# 卷4模块三 Linux第一部分

编辑人：Rannnn

### 题目：

**2.dns部分**
任务描述：创建 DNS 服务器，实现企业域名访问。 
（1）所有 linux 主机启用防火墙，防火墙区域为 public，在防火墙中放行对应服务端口。 
（2）利用 chrony，配置 linux1 为其他 linux 主机提供 NTP 服务。 
（3）所有 linux 主机之间（包含本主机）root 用户实现密钥 ssh 认证，禁用密码认证。 
（4）利用 bind，配置 linux1 为主 DNS 服务器，linux2 为备用 DNS 服务器。为所有 linux 主机提供冗余 DNS 正反向解析服务。 
（5）配置 linux1 为 CA 服务器,为 linux 主机颁发证书。证书颁 发机构有效期 10 年，公用名为 linux1.skills.lan。申请并颁发一张 供 linux 服务器使用的证书，证书信息：有效期=5 年，公用名 =skills.lan，国家=CN，省=Beijing，城市=Beijing，组织=skills， 组织单位=system，使用者可选名称=*.skills.lan 和 skills.lan。将 证书 skills.crt 和私钥 skills.key 复制到需要证书的 linux 服务器/etc/ssl 目录。浏览器访问 https 网站时，不出现证书警告信息。
**3.ansible 服务**
任务描述：请采用 ansible，实现自动化运维。 
（1）在 linux1 上安装 ansible，作为 ansible 的控制节点。 linux2-linux9 作为 ansible 的受控节点。

### 环境：

VMware虚拟环境（比赛为云平台环境，这里为了模拟比赛环境使用虚拟机搭建）

系统：Rocky9.1-1、Rocky9.1-2、Rocky9.1-3、Rocky9.1-4（由于设备受限，最大只能创建四台虚拟机，设备台数按照比赛要求创建）
IP：192.168.152.179、192.168.152.146、192.168.152.187、192.168.152.186（VMware的ip配置混乱，练习时以试题为主）
账号：root（赛题有规定）
密码：pass123（赛题有规定）

### 解题部分：

首先配置IP，以第一台机子为例（这里因为VMware设置ip混乱，我就不按照题目要求再设置ip了）：

```shell
nmcli c m ens160 ipv4.me m ipv4.add 192.168.152.179/24 ipv4.gate 192.168.152.2 ipv4.dns 192.168.152.179
```

题目要求把linux1作为主配置。接下来改主机名，以第一台机子为例，命令为：

```shell
hostnamectl set-hostname linux1.skills.lan	#后面的机子依次为linux2、3、4
```

下面配置yum源，把第一台机子作为本地yum源配置。

把原来的仓库备份一份，命令为：

```shell
[root@linux1 ~]# cd /etc/yum.repos.d
[root@linux1 yum.repos.d]# mkdir bak
[root@linux1 yum.repos.d]# mv * bak
```

创建新的仓库：

```shell
[root@linux1 yum.repos.d]# vim 1.repo
```

内容为：

```shell
[1]
name=1
baseurl=file:///mnt/cdrom/BaseOS
enabled=1
gpgcheck=0

[2]
name=2
baseurl=file:///mnt/cdrom/AppStream
enabled=1
gpgcheck=0
```

在/mnt处新建一个cdrom文件夹

```shell
[root@linux1 ~]# mkdir /mnt/cdrom
```

然后将光盘内的文件挂载到这个文件夹中：

```shell
[root@linux1 ~]# mount /dev/cdrom /mnt/cdrom
[root@linux1 ~]# mount /dev/sr0 /mnt/cdrom
```

输入以下命令生成yum缓存，以验证yum仓库是否生效

```
yum makecache
```

安装httpd，以便搭建局域网yum仓库

```
yum install -y httpd
```

安装成功后，在/var/www/html目录下新建cdrom文件夹

```
mkdir /var/www/html/cdrom
```

将挂载于/mnt/cdrom的光盘卸载，重新挂载于/var/www/html/cdrom目录下

```
[root@linux1 ~]# mount /dev/cdrom /var/www/html/cdrom
[root@linux1 ~]# mount /dev/sr0 /var/www/html/cdrom
```

修改位于/etc/yum.repos.d的1.repo文件，将仓库路径修改为新挂载光盘的路径

```shell
[1]
name=1
baseurl=file:///var/www/html/cdrom/BaseOS
enabled=1
gpgcheck=0

[2]
name=2
baseurl=file:///var/www/html/cdrom/AppStream
enabled=1
gpgcheck=0
```

切换到其他的机子中，将/etc/yum.repos.d中的文件全部按照9.1-1的方法备份，并创建名为1.repo的仓库文件，内容为：

```shell
[1]
name=1
baseurl=http://192.168.152.179/cdrom/BaseOS
enabled=1
gpgcheck=0

[2]
name=2
baseurl=http://192.168.152.179/cdrom/AppStream
enabled=1
gpgcheck=0
```

然后将linux1的httpd服务开启并设置为开机自启：

```shell
systemctl enable httpd --now
```

开启防火墙端口：

```shell
firewall-cmd --add-port=80/tcp
firewall-cmd --add-port=80/tcp --per
```

下面测试一下本地yum源搭建效果，安装dns服务，具体补全的bash和vim包，expect包和bind包，为下面的题目和配置做准备：

```shell
yum install -y bash-* vim-* expect bind*
```

下面配置dns服务，用vim打开/etc/named.conf文件并编辑：

```shell
options {
		listen-on port 53 { any; };  #原本为127.0.0.1，改为any
#		linten-on-v6 port 53 { ::1; };  #ipv6，不用可以注释
......
		allow-query		{ any; };  #原本为localhost，改为any
		allow-transfer	{192.168.152.146;};  #原本没有，因为dns的主备关系，这里的ip地址为备用dns的ip，我这里使用的是第二台机器的ip
```

编辑完后打开named.rfc1912.zones文件并编辑成以下样式：

```shell
zone "skills.lan" IN {
		type master;
		file "1"
};
......
zone "152.168.192.in-addr.arpa" IN {
		type master;
		file "2"
};
```

在终端中输入以下命令：

```shell
[root@linux1 ~]# cd /var/named
[root@linux1 named]# cp -p named.localhost 1  #正向DNS解析
[root@linux1 named]# cp -p named.loopback 2  #反向DNS解析
```

打开文件1并在其尾部加上以下内容（根据实际情况，有几台写几台）：

```shell
linux1 A 192.168.152.179
linux2 A 192.168.152.146
linux3 A 192.168.152.187
linux4 A 192.168.152.186
*	   A 192.168.152.179  #为Apache服务
```

打开文件2并在尾部加上以下内容（根据实际情况，有几台写几台）：

```shell
179 PTR linux1.skills.lan
146 PTR linux2.skills.lan
187 PTR linux3.skills.lan
186 PTR linux4.skills.lan
```

重启dns服务，并设置开机自启：

```shell
systemctl enable named.service --now
```

开放53端口：

```shell
firewall-cmd --add-port=53/tcp --add-port=53/udp
firewall-cmd --add-port=53/tcp --add-port=53/udp --per
```

切换至9.1-2，安装dns服务，编辑/etc/named.conf：

```shell
options {
		listen-on port 53 { any; };  #原本为127.0.0.1，改为any
#		linten-on-v6 port 53 { ::1; };  #ipv6，不用可以注释
......
		allow-query		{ any; };  #原本为localhost，改为any
```

编辑named.rfc1912.zones：

```shell
zone "skills.lan" IN {
		type slave;
		file "1"
		masterfile-format text;
		masters {192.168.152.179;};
};
......
zone "152.168.192.in-addr.arpa" IN {
		type slave;
		file "2"
		masterfile-format text;
		masters {192.168.152.179;};
};
```

重启dns服务，并设置开机自启：

```shell
systemctl enable named.service --now
```

开放53端口（主备都要）：

```shell
firewall-cmd --add-port=53/tcp --add-port=53/udp
firewall-cmd --add-port=53/tcp --add-port=53/udp --per
```

在终端输入以下指令：

```shell
[root@linux2 ~]# cd /var/named
[root@linux2 named]# ls
1	2	data	dynamic		named.empty		......
```

看到1和2文件就说明dns没问题了

接下来写一个ssh免密登录的shell脚本，首先在所有的机子内都输入一遍：ssh-keygen，然后按回车直到没有回显。这样密钥就生成了。下面开始写脚本：

```shell
ssh.sh

#!/usr/bin/expect
for ip in linux{1..4}.skills.lan
do expect -c "
set timeout 2
spawn ssh-copy-id "${ip}"
expect {
"yes/no" {send yes\r;exp_continue}
"password" {send pass123\r}	#pass123为实际机器的密码
};expect eof"
done
```

注意，这个脚本所有机子都要有，可以让这四台机子共享ssh密钥，实现免密登录。

在所有机器的终端中统一输入以下指令：

```shell
cd ~
ssh-keygen
chmod 777 ssh.sh
sh ssh.sh
```

会产生大量回显，正常，等待运行完毕后就会是免密了

来验证一下：

```
[root@linux1 ~]# ssh linux2.skills.lan
Activate the web console with: systemctl enable --now cockpit.socket

Last login: Wed Jun  7 15:46:34 2023 from 192.168.152.179
[root@linux2 ~]# 
```

到这里ssh也就没问题了

下面配置chrony，先安装一下（所有机器）：

```
yum install -y chrony
```

编辑/etc/chrony.conf，将26行和29行的注释去掉，将26行的ip地址改为自己所处网络的网段（所有机器）。

输入以下命令，注释chrony.conf的三四行信息（所有机器）：

```shell
[root@linux1 ~]# sed -i "3,4s/^/#/g" /etc/chrony.conf
```

输入以下命令，将server信息加入到chrony.conf中（所有机器）：

```shell
[root@linux1 ~]# echo "server linux1.skills.lan iburst" >> /etc/chrony.conf
```

重启该服务（所有机器）：

```shell
[root@linux1 ~]# systemctl restart chronyd
```

开放123端口（只需在主配置机器运行）：

```shell
[root@linux1 ~]# firewall-cmd --add-port=123/udp
[root@linux1 ~]# firewall-cmd --add-port=123/udp --per
```

输入以下代码检查服务：

```shell
[root@linux2 ~]# timedatectl
               Local time: 四 2023-06-08 08:15:41 CST
           Universal time: 四 2023-06-08 00:15:41 UTC
                 RTC time: 四 2023-06-08 00:15:41
                Time zone: Asia/Shanghai (CST, +0800)
System clock synchronized: yes  #出现yes信息则为成功，但linux1要是no
              NTP service: active
          RTC in local TZ: no
```

chrony配置完成

下面配置ansible，先进行安装：

```shell
yum install -y ansible-*
```

安装完成后，输入以下指令，将四台机器名输入到文件内：

```shell
for i in {1..4} ;do echo "linux$i.skills.lan" >> /etc/ansible/hosts ;done
```

编辑/etc/ansible/hosts文件，在最底下将linux2-4分为all组，将linux1单独分出：

```shell
linux1.skills.lan
[all]
linux2.skills.lan
linux3.skills.lan
linux4.skills.lan
```

在/root目录下创建skills.yaml文件并编辑：

```shell
- hosts: linux1.skills.lan
  tasks:
    - file:
        path: "/root/ansible.txt"
        state: touch

- hosts: all
  tasks:
    - file:
        path: "/root/ansible.txt"
        state: touch
    - copy:
        src: "/root/ansible.txt"
        dest: "/root"
```

保存退出，输入以下命令：

```shell
[root@linux1 ~]# ansible-playbook skills.yaml
```

等待执行完毕后在/root目录下会出现ansible.txt的文件，所属不用填，已经为root

ansible完成

下面进行CA证书分配，安装openssl服务：

```shell
yum install -y openssl-* --skip-broken
```

来到/etc/pki/CA文件夹下，创建名为index.txt的文件，再将序列号（这里为01）输入至serial文件：

```shell
[root@linux1 ~]# cd /etc/pki/CA
[root@linux1 CA]# touch index.txt
[root@linux1 CA]# echo 01 > serial
```

接下来创建一个cakey：

```shell
[root@linux1 CA]# openssl genrsa -out private/cakey.pem 2048
```

使用cakey创建一个根证书，题目中有要求，记得仔细审题：

```shell
[root@linux1 CA]# openssl req -new -x509 -key private/cakey.pem -days 3650 -out cacert.pem
You are about to be asked to enter information that will be incorporated
into your certificate request.
What you are about to enter is what is called a Distinguished Name or a DN.
There are quite a few fields but you can leave some blank
For some fields there will be a default value,
If you enter '.', the field will be left blank.
-----
Country Name (2 letter code) [XX]:CN  #题目中要求的国家
State or Province Name (full name) []:Beijing  #题目中要求的省份
Locality Name (eg, city) [Default City]:Beijing  #题目中要求的城市
Organization Name (eg, company) [Default Company Ltd]:skills  #题目中要求的组织
Organizational Unit Name (eg, section) []:system  #题目中要求的组织单位
Common Name (eg, your name or your servers hostname) []:linux1.skills.lan  #题目中要求的公用名
Email Address []:
```

下面创建客户端证书skills.key：

```shell
[root@linux1 CA]# openssl genrsa -out skills.key 2048
```

使用skills.key生成skills.csr文件：

```shell
[root@linux1 CA]# openssl req -new -key skills.key -out skills.csr
You are about to be asked to enter information that will be incorporated
into your certificate request.
What you are about to enter is what is called a Distinguished Name or a DN.
There are quite a few fields but you can leave some blank
For some fields there will be a default value,
If you enter '.', the field will be left blank.
-----
Country Name (2 letter code) [XX]:CN  #题目中要求的国家
State or Province Name (full name) []:Beijing  #题目中要求的省份
Locality Name (eg, city) [Default City]:Beijing  #题目中要求的城市
Organization Name (eg, company) [Default Company Ltd]:skills  #题目中要求的组织
Organizational Unit Name (eg, section) []:system  #题目中要求的组织单位
Common Name (eg, your name or your servers hostname) []:skills.lan  #题目中要求的公用名
Email Address []:

Please enter the following 'extra' attributes
to be sent with your certificate request
A challenge password []:
An optional company name []:
```

下面编辑一个使用文件，实现后面的http访问不报错：

```shell
[root@linux1 CA]# vim sign.cnf
subjectAltName = DNS:*.skills.lan,DNS:skills.lan  #将这个语句编辑到sign.cnf中
```

保存退出后输入以下指令，生成一个五年的证书

```shell
[root@linux1 CA]# openssl ca -in skills.csr -out skills.crt -days 1825 -extfile sign.cnf
Using configuration from /etc/pki/tls/openssl.cnf
Check that the request matches the signature
Signature ok
Certificate Details:
        Serial Number: 1 (0x1)
        Validity
            Not Before: Jun  7 10:37:20 2023 GMT
            Not After : Jun  5 10:37:20 2028 GMT
        Subject:
            countryName               = CN
            stateOrProvinceName       = Beijing
            organizationName          = skills
            organizationalUnitName    = system
            commonName                = skills.lan
        X509v3 extensions:
            X509v3 Subject Alternative Name: 
                DNS:*.skills.lan, DNS:skills.lan
Certificate is to be certified until Jun  5 10:37:20 2028 GMT (1825 days)
Sign the certificate? [y/n]:y  #选择y


1 out of 1 certificate requests certified, commit? [y/n]y  #选择y
Write out database with 1 new entries
Data Base Updated
```

在/etc/ssl目录下新建一个com文件夹，将名为skills和cacert的所有文件都拷贝到/etc/ssl/com下，并检查文件是否在该目录下。（试题有要求，我这里因为环境问题故做修改）

```
cp skills.* /etc/ssl/com
cp cacert.* /etc/ssl/com
cd /etc/ssl/com
```

输入下面两条命令，将证书发送至其他机器：

```shell
[root@linux1 com]# for i in {1..4};do mkdir /etc/ssl/com linux$i.skills.lan;done
[root@linux1 com]# for i in {1..4};do scp skills.* linux$i.skills.lan:/etc/ssl/com;done
[root@linux1 com]# for i in {1..4};do scp /etc/pki/CA/cacert.pem linux$i.skills.lan:/opt;done
```

将证书添加至信任机构：

```shell
[root@linux1 ssl]# for i in {1..4};do ssh root@linux$i.skills.lan 'cat /opt/cacert.pem >> /etc/pki/tls/certs/ca-bundle.crt' ;done
```

CA证书配置完成。

至此，dns服务和ansible服务完成。