# 卷4模块三 Linux第二部分

编辑人：Rannnn

### 题目：

**4.apache2服务**
任务描述：请采用Apache搭建企业网站。
（1）配置linux1为Apache2服务器，使用skills.lan或 any.skills.lan（any代表任意网址前缀，用linux1.skills.lan和 web.skills.lan测试）访问时，自动跳转到`www.skills.lan`。禁止使用IP地址访问，默认首页文档/var/www/html/index.html的内容为 "apache"。
（2）把/etc/ssl/skills.crt证书文件和/etc/ssl/skills.key 私钥文件转换成含有证书和私钥的/etc/ssl/skills.pfx文件；然后把 /etc/ssl/skills.pfx转换为含有证书和私钥的/etc/ssl/skills.pem 文件，再从/etc/ssl/skills.pem文件中提取证书和私钥分别到 /etc/ssl/apache.crt和/etc/ssl/apache.key。
（3）客户端访问Apache服务时，必需有ssl证书。

**5.tomcat服务**
任务描述：采用Tomcat搭建动态网站。
（1）配置linux2为nginx服务器，默认文档index.html的内容 为“hellonginx”；仅允许使用域名访问，http访问自动跳转到https。
（2）利用nginx反向代理，实现linux3和linux4的tomcat负载均衡，通过`https://tomcat.skills.lan`加密访问Tomcat，http访 问通过301自动跳转到https。
（3）配置linux3和linux4为tomcat服务器，网站默认首页内容分别为“tomcatA”和“tomcatB”，仅使用域名访问80端口http和 443端口https；证书路径均为/etc/ssl/skills.jks。 

### 环境：

**注意：此文档为第一部分环境的基础上继续进行，如第一部分没做完，建议把第一部分做完了再做接下来的部分。**

VMware虚拟环境（比赛为云平台环境，这里为了模拟比赛环境使用虚拟机搭建）

设备：Rocky9.1-1、Rocky9.1-2、Rocky9.1-3、Rocky9.1-4（由于设备受限，最大只能创建四台虚拟机，设备台数按照比赛要求创建）
IP：192.168.152.101、192.168.152.102、192.168.152.103、192.168.152.104（VMware的ip配置混乱，练习时以试题为主）
账号：root（赛题有规定）
密码：pass123（赛题有规定）

### 解题部分：

首先安装所需工具：httpd（第一部分已经安装）、mod_ssl（起到https的作用）。

```
[root@linux1 ~]# yum install -y mod_ssl
```

编辑/etc/httpd/conf/httpd.conf文件，在结尾处倒数第二行输入以下代码（把最后一行留着！！！）：

```shell
<virtualhost *:80>
DocumentRoot "/var/www/html"
Servername linux1.skills.lan
RewriteEngine On
RewriteRule ^/(.*)$ https://www.skills.lan/$1 [R=301]
<Directory "/">
	Require all granted
</Directory>
</virtualhost>
<virtualhost *:80>
servername 192.168.152.101
redirect 403 /
</virtualhost>
```

保存，编辑/etc/httpd/conf.d/ssl.conf：

```
……
<VirtualHost *:443>  #原来为_default_，修改为*

# General setup for the virtual host, inherited from global configuration
DocumentRoot "/var/www/html"  #将其前面的#去除
ServerName www.skills.lan:443  #将其前面的#去除，并将example.com改为skills.lan
……
#   Some ECC cipher suites (http://www.ietf.org/rfc/rfc4492.txt)
#   require an ECC certificate which can also be configured in
#   parallel.
SSLCertificateFile /etc/ssl/skills.crt  #将目录改为创建证书所在的目录
……
#   you've both a RSA and a DSA private key you can configure
#   both in parallel (to also allow the use of DSA ciphers, etc.)
#   ECC keys, when in use, can also be configured in parallel
SSLCertificateKeyFile /etc/ssl/skills.key  #将目录改为创建密钥所在的目录
……
#   Certificate Authority (CA):
#   Set the CA certificate verification path where to find CA
#   certificates for client authentication or alternatively one
#   huge file containing all of them (file must be PEM encoded)
SSLCACertificateFile /opt/cacert.pem  #将前面的#去除，并将目录改为证书所在的目录

#   Client Authentication (Type):
#   Client certificate verification type and depth.  Types are
#   none, optional, require and optional_no_ca.  Depth is a
#   number which specifies how deeply to verify the certificate
#   issuer chain before deciding the certificate is not valid.
SSLVerifyClient require  #将前面的#去除
SSLVerifyDepth  10   #将前面的#去除
……
（结尾处）
</VirtualHost>
<virtualHost *:443>
servername 192.168.152.179
sslengine on
sslcertificatefile /etc/ssl/skills.crt
sslcertificatekeyfile /etc/ssl/skills.key
redirect 403 /
</virtualHost>
```

简洁版：

40行：

```
<VirtualHost *:443>
```

43、44行：

```
DocumentRoot "/var/www/html"
ServerName www.skills.lan:443
```

85行：

```
SSLCertificateFile /etc/ssl/com/skills.crt
```

93行：

```
SSLCertificateKeyFile /etc/ssl/com/skills.key
```

108行：

```
SSLCACertificateFile /etc/ssl/com/cacert.pem
```

结尾：

```
<virtualhost *:443>
servername 192.168.152.101
sslengine on
sslcertificatefile /etc/ssl/com/skills.crt
sslcertificatekeyfile /etc/ssl/com/skills.key
redirect 403 /
</virtualHost>
```

将“Apache”写入index.html文件：

```
echo "Apache" >> /var/www/html/index.html
```

重启httpd服务

```
systemctl enable httpd --now
service httpd restart
```

保存，输入以下命令，将skills.crt和skills.key合并成skills.pfx文件

```
openssl pkcs12 -export --password pass:pass123 -in skills.crt -inkey skills.key -out skills.pfx  #pass123为密码，赛题有要求
```

输入以下命令，将skills.pfx转化为skills.pem

```
openssl pkcs12 -nodes -passin pass:pass123 -in skills.pfx -out skills.pem
```

输入以下命令，将skills.pem分为apache.crt和apache.key两个文件（文件名题目有要求）

```
openssl x509 -in skills.pem -out apache.crt
openssl rsa -in skills.pem -out apache.key
```

**注意区分x509和rsa的区别**

记得一定要允许443端口通过防火墙

```
firewall-cmd --add-port=443/tcp
firewall-cmd --add-port=443/tcp --per
```

连接linux1，将/etc/ssl/com下的cacert.pem和skills.pfx导出，将cacert.pem后缀名改为cer，安装证书，存储位置为**受信任的根证书颁发机构**，打开浏览器，搜索证书，点击导入，选择skills.pfx文件，输入密码，点击添加，完成。

http://192.168.152.101

显示403

https://192.168.152.101

显示不安全，选择继续，显示403

https://linux1.skills.lan

显示Apache，不警告

Apache结束

下面进行tomcat

以下步骤全部在/root目录下完成

首先安装tar：

```
yum install -y tar
```

使用软件自带的sftp将jdk和apache-tomcat的包拷贝到linux3，4中，解压包

```
tar -zxvf apache-tomcat-10.0.26.tar.gz
tar -zxvf jdk-17_linux-x64_bin.tar.gz
```

将jdk-17.0.7改名为jdk17

```
mv jdk-17.0.7 jdk17
```

将apache-tomcat-10.0.26改名为tomcat10

```
mv apache-tomcat-10.0.26 tomcat10
```

编辑/etc/profile，加入环境变量：

```
echo "PATH=\$PATH:/root/jdk17/bin" >> /etc/profile
```

使配置生效：

```
source /etc/profile
```

查看变量是否生效：

```
java -version
```

能正确出现版本号就说明正常。

进入linux1，修改位于/var/named下的1文件，添加tomcat的dns

```
tomcat	A	192.168.152.102
```

进入tomcat10/webapps/ROOT下，将文件夹内的内容全部删除（两台机子）。

```
cd tomcat10/webapps/ROOT
rm -rf tomcat10/webapps/ROOT/*
```

将TomcatA和TomcatB分别写入linux3和linux4

```
echo "TomcatA" >> /root/tomcat10/webapps/ROOT/index.html
echo "TomcatB" >> /root/tomcat10/webapps/ROOT/index.html
```

编辑tomcat10/conf/server.xml文件

68-70行

```
 68     <Connector port="80" protocol="HTTP/1.1"
 69                connectionTimeout="20000"
 70                redirectPort="443" />
```

86-92行（把上面的"<!——"去掉，这是注释，不去不生效）

```
 86     <Connector port="443" protocol="org.apache.coyote.http11.Http11NioProtocol"
 87                maxThreads="150" SSLEnabled="true">
 88         <UpgradeProtocol className="org.apache.coyote.http2.Http2Protocol" />
 89         <SSLHostConfig>
 90             <Certificate certificateKeystoreFile="/etc/ssl/skills.jks"
 91                         certificateKeystorePassword="pass123"
 92                          type="RSA" />
```

查看证书是否在/etc/ssl目录下，在之前的部分中，我们把证书统一存放在/etc/ssl/com目录下，现在使用cp命令将其中的证书全部复制到/etc/ssl目录下。

```
cd /etc/ssl/com
cp * /etc/ssl
```

在linux3、4中输入以下命令

```
openssl pkcs12 -export -password pass:pass123 -in skills.crt -inkey skills.key -out skills.pfx

keytool -importkeystore -v -srckeystore skills.pfx -srcstoretype pkcs12 -srcstorepass pass123 -destkeystore skills.jks -deststoretype jks -deststorepass pass123
```

查看证书是否生成

进入/root/tomcat10/conf目录下，将server.xml传送至linux4的相同目录中

```
scp server.xml linux4.skills.lan:/root/tomcat10/conf
```

在linux3、4中将jdk17和tomcat10文件夹移动到/opt目录下

```
mv jdk17 /opt
mv tomcat10 /opt
```

因为移动了文件位置，所以环境变量也要修改

```
sed -i "s/root/opt/g" /etc/profile
```

使配置生效

```
source /etc/profile
```

查看是否迁移成功，输入以下命令创建上下文

```
restorecon -Rv /opt
```

输入以下命令，开启tomcat服务

```
sh /opt/tomcat10/bin/startup.sh
```

开放80和443端口

```
firewall-cmd --add-port={80,443}/tcp
firewall-cmd --add-port={80,443}/tcp --per
```

验证是否能访问

```
curl linux3.skills.lan
TomcatA
curl linux4.skills.lan
TomcatB
```

将"source /ect/profile"写入/etc/rc.local文件中

```
echo "source /etc/profile" >> /etc/rc.local
```

将脚本写入rc.local文件中

```
echo "sh /opt/tomcat10/bin/startup.sh" >> /etc/rc.local
```

给权限

```
chmod +x /etc/rc.d/rc.local
```

在linux2中安装nginx服务

```
yum install -y nginx
```

编辑nginx.conf

```
cd /etc/nginx
vim nginx.conf
```

输入`42,58s/^/#/g`，将不需要的代码全部注释

删除ipv6格式的80端口监听

将server_name后面改为linux2.skills.lan;  记住结尾有分号，不要落了

在下面一行还有：

```
return 301 https://\$server_name\$request_uri;
}  #一定要加这个花括号回括
```

将59-68的注释去除（61行不去），修改成下列样式

```
 59     server {
 60         listen       443 ssl;
 61 #        listen       [::]:443 ssl http2;
 62         server_name  linux2.skills.lan;
 63
 64         root         /usr/share/nginx/html;
 65
 66         ssl_certificate "/etc/ssl/com/skills.crt";
 67         ssl_certificate_key "/etc/ssl/com/skills.key";
 68         }
```

将"HelloNginx"写入/usr/share/nginx/html/index.html文件中

```
echo "HelloNginx" > /usr/share/nginx/html/index.html
```

回退到etc目录下，进入nginx/conf.d目录下，查看到该目录下没有配置文件，开始编写配置文件

```
cd nginx/conf.d
vim tomcat.conf
```

```
upstream tomcat {
		server linux3.skills.lan:443;
		server linux4.skills.lan:443;
}
server {
		listen 80;
		server_name tomcat.skills.lan;
		return 301 https://\$server_name\$request_uri;
}
server {
		listen 443 ssl;
		server_name tomcat.skills.lan;
		ssl_certificate /etc/ssl/com/skills.crt;
		ssl_certificate_key /etc/ssl/com/skills.key;
		location / {
					proxy_pass https://tomcat;
		}
}
```

保存退出，编辑default.conf文件

```
vim default.conf
```

```
server {
		listen 80 default_server;
		server_name _;
		return 403;
}
server {
		listen 443 ssl;
		server_name _;
		ssl_certificate /etc/ssl/com/skills.crt;
		ssl_certificate_key  /etc/ssl/com/skills.key;
		return 403;
}
```

保存退出，重启nginx服务并设置开机自启

```
systemctl enable nginx --now
```

开放80，443端口

```
firewall-cmd --add-port={80,443}/tcp
firewall-cmd --add-port={80,443}/tcp --per
```

开启nginx规则

```
setsebool -P httpd_can_network_connect on
```

测试

```
curl https://linux2.skills.lan
HelloNginx

curl https://linux3.skills.lan
TomcatA

curl https://linux4.skills.lan
TomcatB

curl https://tomcat.skills.lan
TomcatA
TomcatB
```

至此，tomcat部分完成

