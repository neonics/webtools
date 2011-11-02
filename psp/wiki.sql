create table wiki_articles (
  id serial not null primary key,
  title varchar(255),
  "date" date,
  status varchar(64),
  text text
);


