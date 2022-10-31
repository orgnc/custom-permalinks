create table if not exists wtapp_custom_permalinks (
	id            int auto_increment primary key,
	post_id       int        not null,
	meta_value    text       null,
	language_code varchar(2) not null,
	constraint post_id_language_code
		unique (post_id, language_code),
	constraint wtapp_custom_permalinks_id_uindex
		unique (id)
);
