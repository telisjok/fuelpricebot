create schema fuelprices CHARACTER SET utf8 COLLATE utf8_general_ci;

create table dd
(
	id varchar(255) not null,
	dd_descr varchar(255) null,
	dimos_descr varchar(255) null,
	nomos_descr varchar(255) null,
	constraint table_name_pk
		primary key (id)
);

create table stations
(
	id int not null,
	name varchar(255) null,
	address varchar(255) null,
	zipcode int null,
	dd_code varchar(255) not null,
	constraint stations_pk
		primary key (id)
);

create table products
(
	id int not null,
	description varchar(255) null,
	company_id int not null,
	constraint products_pk
		primary key (id)
);

create table station_product_map
(
	station_id int not null,
	product_id int not null,
	price double null,
	last_edit timestamp null
);

create table companies
(
	id int not null,
	name varchar(255) null,
	constraint company_pk
		primary key (id)
);

alter table stations
	add constraint stations_dd_fk
		foreign key (dd_code) references dd (id);

alter table station_product_map
	add constraint station_product_map_station_fk
		foreign key (station_id) references stations (id);

alter table station_product_map
	add constraint station_product_map_products_fk
		foreign key (product_id) references products (id);

alter table station_product_map
	add constraint station_product_map_pk
		unique (station_id, product_id);

alter table products
	add constraint products_companies_fk
		foreign key (company_id) references companies (id);