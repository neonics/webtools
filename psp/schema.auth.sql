-- AUTHENTICATION and AUTHORIZATION
BEGIN;

CREATE SCHEMA auth;
SET search_path TO auth,public;
-- \i search_path.sql


CREATE TYPE user_status AS ENUM('new','normal','disabled');

-- Create table 'users'
-- NOTE: use "SELECT .. FROM ONLY users" for admin accounts (that transcend merchant and client accounts)
-- (select from ONLY does not list results in child tables)

CREATE TABLE users (
	id		SERIAL NOT NULL,
	username	varchar (32) NOT NULL,
	password	varchar (32) NOT NULL,
	password_type	varchar (32) NOT NULL,
	activation_code	varchar (32) NULL,
	creation_date	timestamptz NOT NULL default current_date,
	creation_ip	varchar (64) NOT NULL default 'system',
	email		varchar (255) NULL,	-- was not null
	nick		varchar (32) NULL,	-- was not null
	real_name	varchar (128) NULL,
	status		user_status not null default 'new',
	
	PRIMARY KEY (id)
);

insert into users (username, password, password_type, status) values ('admin', 'admin', 'plain', 'normal');

-- Create table 'realms'


CREATE TABLE realms (
	id		SERIAL NOT NULL,
	name		varchar (32) NOT NULL,
	description	varchar (255) NULL,
	
	PRIMARY KEY (id),
	unique (name)
);

INSERT INTO realms (name) VALUES ('core');



-- Create table 'permissions'

CREATE TABLE permissions (
	id		SERIAL NOT NULL,
	realm_id	int4 NOT NULL REFERENCES realms(id),
	name		varchar (128) NULL,
	description	varchar (255) NULL,
	
	PRIMARY KEY (id),
	unique (realm_id, name)
);

insert into permissions (name, realm_id) values ('realm-list', 1);
insert into permissions (name, realm_id) values ('realm-show', 1);
insert into permissions (name, realm_id) values ('realm-store', 1);
insert into permissions (name, realm_id) values ('realm-delete', 1);

insert into permissions (name, realm_id) values ('role-list', 1);
insert into permissions (name, realm_id) values ('role-show', 1);
insert into permissions (name, realm_id) values ('role-store', 1);
insert into permissions (name, realm_id) values ('role-delete', 1);

insert into permissions (name, realm_id) values ('permission-list', 1);
insert into permissions (name, realm_id) values ('permission-show', 1);
insert into permissions (name, realm_id) values ('permission-store', 1);
insert into permissions (name, realm_id) values ('permission-delete', 1);

insert into permissions (name, realm_id) values ('user-list', 1);
insert into permissions (name, realm_id) values ('user-show', 1);
insert into permissions (name, realm_id) values ('user-store', 1);
insert into permissions (name, realm_id) values ('user-delete', 1);

insert into permissions (name, realm_id) values ('user-self-show', 1);



-- Create table 'roles'

CREATE TABLE roles (
	id		SERIAL NOT NULL,
	realm_id	int4 NOT NULL REFERENCES realms(id),
	name		varchar (128) NULL,
	description	varchar (255) NULL,
	
	PRIMARY KEY (id),
	unique (realm_id, name)
);

insert into roles (name, realm_id) values ('admin', 1);


-- Create table 'role_permissions'

CREATE TABLE role_permissions (
	role_id		int4 NOT NULL REFERENCES roles(id),
	permission_id	int4 NOT NULL REFERENCES permissions(id),
	realm_id	int4 NOT NULL REFERENCES realms(id),
	
	PRIMARY KEY (role_id,permission_id),
	UNIQUE (role_id,permission_id,realm_id)
);

insert into role_permissions (role_id, permission_id, realm_id) values (1, 1, 1);
insert into role_permissions (role_id, permission_id, realm_id) values (1, 2, 1);
insert into role_permissions (role_id, permission_id, realm_id) values (1, 3, 1);
insert into role_permissions (role_id, permission_id, realm_id) values (1, 4, 1);

insert into role_permissions (role_id, permission_id, realm_id) values (1, 5, 1);
insert into role_permissions (role_id, permission_id, realm_id) values (1, 6, 1);
insert into role_permissions (role_id, permission_id, realm_id) values (1, 7, 1);
insert into role_permissions (role_id, permission_id, realm_id) values (1, 8, 1);

insert into role_permissions (role_id, permission_id, realm_id) values (1, 9, 1);
insert into role_permissions (role_id, permission_id, realm_id) values (1, 10, 1);
insert into role_permissions (role_id, permission_id, realm_id) values (1, 11, 1);
insert into role_permissions (role_id, permission_id, realm_id) values (1, 12, 1);

insert into role_permissions (role_id, permission_id, realm_id) values (1, 13, 1);
insert into role_permissions (role_id, permission_id, realm_id) values (1, 14, 1);
insert into role_permissions (role_id, permission_id, realm_id) values (1, 15, 1);
insert into role_permissions (role_id, permission_id, realm_id) values (1, 16, 1);
insert into role_permissions (role_id, permission_id, realm_id) values (1, 17, 1);


-- Create table 'user_realms'

CREATE TABLE user_realms (
	user_id		int4 NOT NULL REFERENCES users(id),
	realm_id	int4 NOT NULL REFERENCES realms(id),
	
	PRIMARY KEY (user_id,realm_id)
);

insert into user_realms (user_id, realm_id) values (1, 1);

-- Create table 'user_roles'

CREATE TABLE user_roles (
	role_id		int4 NOT NULL REFERENCES roles(id),
	user_id		int4 NOT NULL REFERENCES users(id),
	realm_id	int4 NOT NULL REFERENCES realms(id),
	
	PRIMARY KEY (role_id,user_id),
	UNIQUE (role_id,user_id,realm_id)
);

insert into user_roles (user_id, role_id, realm_id) values (1, 1, 1);

COMMIT;
