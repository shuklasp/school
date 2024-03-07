-- Active: 1680438883134@@127.0.0.1@3306@school
#Table users
# Stores login ID's
drop table if exists users;

create table users(
uid    integer primary key auto_increment,
uname  varchar(10)  unique not null,
passwd blob,
enabled   char(1)) engine=innodb;

insert into users values(1,'admin',aes_encrypt('hello','hello'),'Y');
insert into users values(2,'op1',aes_encrypt('hello','hello'),'Y');
insert into users values(3,'op2',aes_encrypt('hello','hello'),'Y');
insert into users values(4,'op3',aes_encrypt('hello','hello'),'Y');
insert into users values(5,'op4',aes_encrypt('hello','hello'),'Y');



# Table loginrec
# Keeps record of logins

drop table if exists loginrec;

create table loginrec(
sessid     varchar(100) primary key,
uid integer references users(uid),
logintime   datetime,
ipaddr  varchar(16),
lastaccess   datetime) engine=innodb;


# Table roles
# Keeps record of roles defined in the system.

drop table if exists roles;

create table roles(
roleid   integer primary key auto_increment,
rolename varchar(50))  engine=innodb;

insert into roles values(1,'Admin');
insert into roles values(2,'Operator');

# Table rights
# Stores all the rights available in the system.

drop table if exists rights;

create table rights(
rightid    integer primary key  auto_increment,
rightname  varchar(50))  engine=innodb;

# Table roleright
# Stores rights associated with a perticular role.

drop table if exists roleright;

create table roleright(
roleid     integer references roles(roleid),
rightid    integer references rights(rightid))  engine=innodb;

# Table userroles
# Assigns a perticular role to a user.

drop table if exists userroles;

create table userroles(
uid      integer   references users(uid),
roleid   integer   references roles(roleid))  engine=innodb;

drop table if exists sequences;

create table sequences(
seqname    varchar(20) primary key,
initval    bigint not null,
seqval     bigint not null,
incval     integer not null,
lastaccess bigint not null) engine=innodb;

insert into sequences values('memberid',1000,1000,1,0);
insert into sequences values('nomid',1000,1000,1,0);
insert into sequences values('transid',1000000,1000000,1,0);
insert into sequences values('loggerid',1000000,1000000,1,0);

drop table if exists config;

create table config(
propname     varchar(100) primary key,
propval      varchar(500) not null,
tabname      varchar(100),
colname      varchar(100),
pkname       varchar(100),
pkval        varchar(100)) engine=innodb;

insert into config(propname,propval) values('policypay','10000');
insert into config(propname,propval) values('maxfirstmemlimit','10');
insert into config(propname,propval) values('poladvdays','15');
insert into config(propname,propval) values('memadvdays','15');
insert into config(propname,propval) values('restcomm','0.05');
insert into config(propname,propval) values('tds','11.33');
insert into config(propname,propval) values('max_higher_comm_days','365');
insert into config(propname,propval) values('min_second_comm_days','183');
insert into config(propname,propval) values('adminemail','sudhangshu.mukherjee@sambhavnamarketing.com');
insert into config values('policyvalidity','fromtabs','paytypes','validity','paytypeid','1');
insert into config values('policypayment','fromtabs','paytypes','payval','paytypeid','1');
insert into config values('memvalidity','fromtabs','paytypes','validity','paytypeid','2');
insert into config values('mempayment','fromtabs','paytypes','payval','paytypeid','2');
insert into config(propname,propval) values('poladvalert','15');
insert into config(propname,propval) values('memadvalert','15');

insert into config(propname,propval) values('user_session_timeout','60');


drop table if exists logger;

create table logger(
loggerid    varchar(20) primary key,
uid         integer,
uname       varchar(10),
ip          varchar(16),
logtime     datetime,
sessid      varchar(100),
descr       varchar(10000)) engine=innodb;

drop table if exists dropdowns;

create table dropdowns(
ddid           varchar(10),
optgroupname   varchar(50),
optname        varchar(50));

