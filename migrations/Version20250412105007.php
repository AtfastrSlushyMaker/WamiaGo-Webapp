<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250412105007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE announcement CHANGE id_transporter id_transporter INT DEFAULT NULL, CHANGE title title VARCHAR(255) NOT NULL, CHANGE date date DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE announcement ADD CONSTRAINT FK_4DB9D91CB8242F23 FOREIGN KEY (id_transporter) REFERENCES driver (id_driver)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4DB9D91CB8242F23 ON announcement (id_transporter)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle DROP FOREIGN KEY bicycle_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle DROP FOREIGN KEY bicycle_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle CHANGE id_station id_station INT DEFAULT NULL, CHANGE last_updated last_updated DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle ADD CONSTRAINT FK_D81AFAAE41A451C1 FOREIGN KEY (id_station) REFERENCES bicycle_station (id_station)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX bicycle_ibfk_1 ON bicycle
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D81AFAAE41A451C1 ON bicycle (id_station)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle ADD CONSTRAINT bicycle_ibfk_1 FOREIGN KEY (id_station) REFERENCES bicycle_station (id_station) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental DROP FOREIGN KEY bicycle_rental_ibfk_2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental DROP FOREIGN KEY bicycle_rental_ibfk_3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental DROP FOREIGN KEY bicycle_rental_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental DROP FOREIGN KEY bicycle_rental_ibfk_4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental DROP FOREIGN KEY bicycle_rental_ibfk_2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental DROP FOREIGN KEY bicycle_rental_ibfk_3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental DROP FOREIGN KEY bicycle_rental_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental DROP FOREIGN KEY bicycle_rental_ibfk_4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental CHANGE id_user id_user INT DEFAULT NULL, CHANGE id_bike id_bike INT DEFAULT NULL, CHANGE id_start_station id_start_station INT DEFAULT NULL, CHANGE start_time start_time DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental ADD CONSTRAINT FK_8507A9E76B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental ADD CONSTRAINT FK_8507A9E7C79C2B82 FOREIGN KEY (id_bike) REFERENCES bicycle (id_bike)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental ADD CONSTRAINT FK_8507A9E7D1D47507 FOREIGN KEY (id_start_station) REFERENCES bicycle_station (id_station)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental ADD CONSTRAINT FK_8507A9E712E464AF FOREIGN KEY (id_end_station) REFERENCES bicycle_station (id_station)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX bicycle_rental_ibfk_1 ON bicycle_rental
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8507A9E76B3CA4B ON bicycle_rental (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX bicycle_rental_ibfk_2 ON bicycle_rental
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8507A9E7C79C2B82 ON bicycle_rental (id_bike)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX bicycle_rental_ibfk_3 ON bicycle_rental
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8507A9E7D1D47507 ON bicycle_rental (id_start_station)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX bicycle_rental_ibfk_4 ON bicycle_rental
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8507A9E712E464AF ON bicycle_rental (id_end_station)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental ADD CONSTRAINT bicycle_rental_ibfk_2 FOREIGN KEY (id_bike) REFERENCES bicycle (id_bike) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental ADD CONSTRAINT bicycle_rental_ibfk_3 FOREIGN KEY (id_start_station) REFERENCES bicycle_station (id_station) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental ADD CONSTRAINT bicycle_rental_ibfk_1 FOREIGN KEY (id_user) REFERENCES user (id_user) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental ADD CONSTRAINT bicycle_rental_ibfk_4 FOREIGN KEY (id_end_station) REFERENCES bicycle_station (id_station) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_station DROP FOREIGN KEY bicycle_station_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_station DROP FOREIGN KEY bicycle_station_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_station CHANGE id_location id_location INT DEFAULT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_station ADD CONSTRAINT FK_8F709C80E45655E FOREIGN KEY (id_location) REFERENCES location (id_location)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX bicycle_station_ibfk_1 ON bicycle_station
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8F709C80E45655E ON bicycle_station (id_location)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_station ADD CONSTRAINT bicycle_station_ibfk_1 FOREIGN KEY (id_location) REFERENCES location (id_location) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking DROP FOREIGN KEY booking_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking DROP FOREIGN KEY booking_ibfk_2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking DROP FOREIGN KEY booking_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking DROP FOREIGN KEY booking_ibfk_2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking CHANGE id_trip id_trip INT DEFAULT NULL, CHANGE id_passenger id_passenger INT DEFAULT NULL, CHANGE status status VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEFD76E939 FOREIGN KEY (id_trip) REFERENCES trip (id_trip)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEBBDA54FA FOREIGN KEY (id_passenger) REFERENCES user (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX booking_ibfk_2 ON booking
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E00CEDDEFD76E939 ON booking (id_trip)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX booking_ibfk_1 ON booking
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E00CEDDEBBDA54FA ON booking (id_passenger)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking ADD CONSTRAINT booking_ibfk_1 FOREIGN KEY (id_passenger) REFERENCES user (id_user) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking ADD CONSTRAINT booking_ibfk_2 FOREIGN KEY (id_trip) REFERENCES trip (id_trip) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE driver DROP FOREIGN KEY driver_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX permit_number ON driver
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE driver DROP FOREIGN KEY driver_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE driver CHANGE id_user id_user INT DEFAULT NULL, CHANGE permit_number permit_number VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE driver ADD CONSTRAINT FK_11667CD96B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX driver_ibfk_1 ON driver
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_11667CD96B3CA4B ON driver (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE driver ADD CONSTRAINT driver_ibfk_1 FOREIGN KEY (id_user) REFERENCES user (id_user) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE location CHANGE latitude latitude NUMERIC(10, 0) NOT NULL, CHANGE longitude longitude NUMERIC(10, 0) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rating DROP FOREIGN KEY rating_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rating DROP FOREIGN KEY rating_ibfk_2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rating DROP FOREIGN KEY rating_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rating DROP FOREIGN KEY rating_ibfk_2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rating CHANGE id_user id_user INT DEFAULT NULL, CHANGE id_driver id_driver INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rating ADD CONSTRAINT FK_D88926226B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rating ADD CONSTRAINT FK_D88926223751C934 FOREIGN KEY (id_driver) REFERENCES driver (id_driver)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX rating_ibfk_2 ON rating
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D88926226B3CA4B ON rating (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX rating_ibfk_1 ON rating
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D88926223751C934 ON rating (id_driver)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rating ADD CONSTRAINT rating_ibfk_1 FOREIGN KEY (id_driver) REFERENCES driver (id_driver) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rating ADD CONSTRAINT rating_ibfk_2 FOREIGN KEY (id_user) REFERENCES user (id_user) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation DROP FOREIGN KEY reclamation_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation DROP FOREIGN KEY reclamation_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation CHANGE id_user id_user INT DEFAULT NULL, CHANGE date date DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation ADD CONSTRAINT FK_CE6064046B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX reclamation_ibfk_1 ON reclamation
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CE6064046B3CA4B ON reclamation (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation ADD CONSTRAINT reclamation_ibfk_1 FOREIGN KEY (id_user) REFERENCES user (id_user) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relocation DROP FOREIGN KEY relocation_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relocation DROP FOREIGN KEY relocation_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relocation CHANGE id_reservation id_reservation INT DEFAULT NULL, CHANGE date date DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relocation ADD CONSTRAINT FK_3C7EAF9A5ADA84A2 FOREIGN KEY (id_reservation) REFERENCES reservation (id_reservation)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX relocation_ibfk_1 ON relocation
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3C7EAF9A5ADA84A2 ON relocation (id_reservation)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relocation ADD CONSTRAINT relocation_ibfk_1 FOREIGN KEY (id_reservation) REFERENCES reservation (id_reservation) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request DROP FOREIGN KEY fk_request_arrival_location
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request DROP FOREIGN KEY fk_request_client
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request DROP FOREIGN KEY fk_request_departure_location
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request DROP FOREIGN KEY fk_request_arrival_location
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request DROP FOREIGN KEY fk_request_client
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request DROP FOREIGN KEY fk_request_departure_location
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request CHANGE status status VARCHAR(255) NOT NULL, CHANGE request_date request_date DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request ADD CONSTRAINT FK_3B978F9FE173B1B8 FOREIGN KEY (id_client) REFERENCES user (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request ADD CONSTRAINT FK_3B978F9F61C09F39 FOREIGN KEY (id_departure_location) REFERENCES location (id_location)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request ADD CONSTRAINT FK_3B978F9FB3049FE6 FOREIGN KEY (id_arrival_location) REFERENCES location (id_location)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX fk_request_client ON request
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3B978F9FE173B1B8 ON request (id_client)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX fk_request_departure_location ON request
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3B978F9F61C09F39 ON request (id_departure_location)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX fk_request_arrival_location ON request
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3B978F9FB3049FE6 ON request (id_arrival_location)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request ADD CONSTRAINT fk_request_arrival_location FOREIGN KEY (id_arrival_location) REFERENCES location (id_location) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request ADD CONSTRAINT fk_request_client FOREIGN KEY (id_client) REFERENCES user (id_user) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request ADD CONSTRAINT fk_request_departure_location FOREIGN KEY (id_departure_location) REFERENCES location (id_location) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY fk_reservation_user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY reservation_ibfk_3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY reservation_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY reservation_ibfk_2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY fk_reservation_user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY reservation_ibfk_3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY reservation_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY reservation_ibfk_2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation CHANGE id_start_location id_start_location INT DEFAULT NULL, CHANGE id_end_location id_end_location INT DEFAULT NULL, CHANGE id_announcement id_announcement INT DEFAULT NULL, CHANGE date date DATETIME NOT NULL, CHANGE status status VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT FK_42C849557CD272FF FOREIGN KEY (id_start_location) REFERENCES location (id_location)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A41C6934 FOREIGN KEY (id_end_location) REFERENCES location (id_location)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT FK_42C849556E78AC6E FOREIGN KEY (id_announcement) REFERENCES announcement (id_announcement)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT FK_42C849556B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX reservation_ibfk_1 ON reservation
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_42C849557CD272FF ON reservation (id_start_location)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX reservation_ibfk_2 ON reservation
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_42C84955A41C6934 ON reservation (id_end_location)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX reservation_ibfk_3 ON reservation
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_42C849556E78AC6E ON reservation (id_announcement)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX fk_reservation_user ON reservation
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_42C849556B3CA4B ON reservation (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT fk_reservation_user FOREIGN KEY (id_user) REFERENCES user (id_user) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT reservation_ibfk_3 FOREIGN KEY (id_announcement) REFERENCES announcement (id_announcement) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT reservation_ibfk_1 FOREIGN KEY (id_start_location) REFERENCES location (id_location) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT reservation_ibfk_2 FOREIGN KEY (id_end_location) REFERENCES location (id_location) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE response DROP FOREIGN KEY response_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE response DROP FOREIGN KEY response_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE response CHANGE id_reclamation id_reclamation INT DEFAULT NULL, CHANGE date date DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE response ADD CONSTRAINT FK_3E7B0BFBD672A9F3 FOREIGN KEY (id_reclamation) REFERENCES reclamation (id_reclamation)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX response_ibfk_1 ON response
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3E7B0BFBD672A9F3 ON response (id_reclamation)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE response ADD CONSTRAINT response_ibfk_1 FOREIGN KEY (id_reclamation) REFERENCES reclamation (id_reclamation) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride DROP FOREIGN KEY ride_ibfk_3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride DROP FOREIGN KEY fk_id_taxi
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride DROP FOREIGN KEY ride_ibfk_3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride DROP FOREIGN KEY fk_id_taxi
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride CHANGE id_request id_request INT DEFAULT NULL, CHANGE distance distance NUMERIC(10, 0) DEFAULT NULL, CHANGE price price NUMERIC(10, 0) DEFAULT NULL, CHANGE status status VARCHAR(255) NOT NULL, CHANGE ride_date ride_date DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride ADD CONSTRAINT FK_9B3D7CD0E50A26EF FOREIGN KEY (id_request) REFERENCES request (id_request)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride ADD CONSTRAINT FK_9B3D7CD0D4A47FC0 FOREIGN KEY (id_taxi) REFERENCES driver (id_driver)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX ride_ibfk_3 ON ride
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9B3D7CD0E50A26EF ON ride (id_request)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX fk_id_taxi ON ride
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9B3D7CD0D4A47FC0 ON ride (id_taxi)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride ADD CONSTRAINT ride_ibfk_3 FOREIGN KEY (id_request) REFERENCES request (id_request) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride ADD CONSTRAINT fk_id_taxi FOREIGN KEY (id_taxi) REFERENCES driver (id_driver) ON UPDATE CASCADE ON DELETE SET NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trip DROP FOREIGN KEY trip_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trip DROP FOREIGN KEY trip_ibfk_2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trip DROP FOREIGN KEY trip_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trip DROP FOREIGN KEY trip_ibfk_2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trip CHANGE id_driver id_driver INT DEFAULT NULL, CHANGE id_vehicle id_vehicle INT DEFAULT NULL, CHANGE departure_date departure_date DATETIME NOT NULL, CHANGE price_per_passenger price_per_passenger NUMERIC(10, 0) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trip ADD CONSTRAINT FK_7656F53B3751C934 FOREIGN KEY (id_driver) REFERENCES driver (id_driver)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trip ADD CONSTRAINT FK_7656F53BC51D4DF6 FOREIGN KEY (id_vehicle) REFERENCES vehicle (id_vehicle)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX trip_ibfk_2 ON trip
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_7656F53B3751C934 ON trip (id_driver)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX trip_ibfk_1 ON trip
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_7656F53BC51D4DF6 ON trip (id_vehicle)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trip ADD CONSTRAINT trip_ibfk_1 FOREIGN KEY (id_vehicle) REFERENCES vehicle (id_vehicle) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trip ADD CONSTRAINT trip_ibfk_2 FOREIGN KEY (id_driver) REFERENCES driver (id_driver) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP FOREIGN KEY user_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX email ON user
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX phone_number ON user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP FOREIGN KEY user_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD first_name VARCHAR(255) NOT NULL, ADD last_name VARCHAR(255) NOT NULL, DROP name, CHANGE email email VARCHAR(255) NOT NULL, CHANGE phone_number phone_number VARCHAR(255) NOT NULL, CHANGE account_status account_status VARCHAR(20) DEFAULT 'ACTIVE' NOT NULL, CHANGE status status VARCHAR(20) DEFAULT 'OFFLINE' NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD CONSTRAINT FK_8D93D649E45655E FOREIGN KEY (id_location) REFERENCES location (id_location)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX user_ibfk_1 ON user
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D93D649E45655E ON user (id_location)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD CONSTRAINT user_ibfk_1 FOREIGN KEY (id_location) REFERENCES location (id_location) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE vehicle DROP FOREIGN KEY vehicle_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX registration ON vehicle
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE vehicle DROP FOREIGN KEY vehicle_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE vehicle CHANGE id_driver id_driver INT DEFAULT NULL, CHANGE registration registration VARCHAR(255) NOT NULL, CHANGE color color VARCHAR(255) NOT NULL, CHANGE model model VARCHAR(255) NOT NULL, CHANGE brand brand VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E4863751C934 FOREIGN KEY (id_driver) REFERENCES driver (id_driver)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX vehicle_ibfk_1 ON vehicle
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1B80E4863751C934 ON vehicle (id_driver)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE vehicle ADD CONSTRAINT vehicle_ibfk_1 FOREIGN KEY (id_driver) REFERENCES driver (id_driver) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE announcement DROP FOREIGN KEY FK_4DB9D91CB8242F23
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE announcement DROP FOREIGN KEY FK_4DB9D91CB8242F23
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE announcement CHANGE id_transporter id_transporter INT NOT NULL, CHANGE title title VARCHAR(50) NOT NULL, CHANGE date date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_4db9d91cb8242f23 ON announcement
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX announcement_ibfk_1 ON announcement (id_transporter)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE announcement ADD CONSTRAINT FK_4DB9D91CB8242F23 FOREIGN KEY (id_transporter) REFERENCES driver (id_driver)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle DROP FOREIGN KEY FK_D81AFAAE41A451C1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle DROP FOREIGN KEY FK_D81AFAAE41A451C1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle CHANGE id_station id_station INT NOT NULL, CHANGE last_updated last_updated DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle ADD CONSTRAINT bicycle_ibfk_1 FOREIGN KEY (id_station) REFERENCES bicycle_station (id_station) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_d81afaae41a451c1 ON bicycle
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX bicycle_ibfk_1 ON bicycle (id_station)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle ADD CONSTRAINT FK_D81AFAAE41A451C1 FOREIGN KEY (id_station) REFERENCES bicycle_station (id_station)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental DROP FOREIGN KEY FK_8507A9E76B3CA4B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental DROP FOREIGN KEY FK_8507A9E7C79C2B82
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental DROP FOREIGN KEY FK_8507A9E7D1D47507
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental DROP FOREIGN KEY FK_8507A9E712E464AF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental DROP FOREIGN KEY FK_8507A9E76B3CA4B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental DROP FOREIGN KEY FK_8507A9E7C79C2B82
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental DROP FOREIGN KEY FK_8507A9E7D1D47507
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental DROP FOREIGN KEY FK_8507A9E712E464AF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental CHANGE id_user id_user INT NOT NULL, CHANGE id_bike id_bike INT NOT NULL, CHANGE id_start_station id_start_station INT NOT NULL, CHANGE start_time start_time DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental ADD CONSTRAINT bicycle_rental_ibfk_2 FOREIGN KEY (id_bike) REFERENCES bicycle (id_bike) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental ADD CONSTRAINT bicycle_rental_ibfk_3 FOREIGN KEY (id_start_station) REFERENCES bicycle_station (id_station) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental ADD CONSTRAINT bicycle_rental_ibfk_1 FOREIGN KEY (id_user) REFERENCES user (id_user) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental ADD CONSTRAINT bicycle_rental_ibfk_4 FOREIGN KEY (id_end_station) REFERENCES bicycle_station (id_station) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_8507a9e76b3ca4b ON bicycle_rental
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX bicycle_rental_ibfk_1 ON bicycle_rental (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_8507a9e7c79c2b82 ON bicycle_rental
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX bicycle_rental_ibfk_2 ON bicycle_rental (id_bike)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_8507a9e7d1d47507 ON bicycle_rental
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX bicycle_rental_ibfk_3 ON bicycle_rental (id_start_station)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_8507a9e712e464af ON bicycle_rental
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX bicycle_rental_ibfk_4 ON bicycle_rental (id_end_station)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental ADD CONSTRAINT FK_8507A9E76B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental ADD CONSTRAINT FK_8507A9E7C79C2B82 FOREIGN KEY (id_bike) REFERENCES bicycle (id_bike)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental ADD CONSTRAINT FK_8507A9E7D1D47507 FOREIGN KEY (id_start_station) REFERENCES bicycle_station (id_station)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_rental ADD CONSTRAINT FK_8507A9E712E464AF FOREIGN KEY (id_end_station) REFERENCES bicycle_station (id_station)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_station DROP FOREIGN KEY FK_8F709C80E45655E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_station DROP FOREIGN KEY FK_8F709C80E45655E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_station CHANGE id_location id_location INT NOT NULL, CHANGE name name VARCHAR(50) NOT NULL, CHANGE status status VARCHAR(255) DEFAULT 'active' NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_station ADD CONSTRAINT bicycle_station_ibfk_1 FOREIGN KEY (id_location) REFERENCES location (id_location) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_8f709c80e45655e ON bicycle_station
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX bicycle_station_ibfk_1 ON bicycle_station (id_location)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bicycle_station ADD CONSTRAINT FK_8F709C80E45655E FOREIGN KEY (id_location) REFERENCES location (id_location)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEFD76E939
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEBBDA54FA
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEFD76E939
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEBBDA54FA
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking CHANGE id_trip id_trip INT NOT NULL, CHANGE id_passenger id_passenger INT NOT NULL, CHANGE status status VARCHAR(255) DEFAULT 'Pending' NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking ADD CONSTRAINT booking_ibfk_1 FOREIGN KEY (id_passenger) REFERENCES user (id_user) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking ADD CONSTRAINT booking_ibfk_2 FOREIGN KEY (id_trip) REFERENCES trip (id_trip) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_e00ceddebbda54fa ON booking
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX booking_ibfk_1 ON booking (id_passenger)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_e00ceddefd76e939 ON booking
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX booking_ibfk_2 ON booking (id_trip)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEFD76E939 FOREIGN KEY (id_trip) REFERENCES trip (id_trip)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEBBDA54FA FOREIGN KEY (id_passenger) REFERENCES user (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE driver DROP FOREIGN KEY FK_11667CD96B3CA4B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE driver DROP FOREIGN KEY FK_11667CD96B3CA4B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE driver CHANGE id_user id_user INT NOT NULL, CHANGE permit_number permit_number VARCHAR(20) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE driver ADD CONSTRAINT driver_ibfk_1 FOREIGN KEY (id_user) REFERENCES user (id_user) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX permit_number ON driver (permit_number)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_11667cd96b3ca4b ON driver
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX driver_ibfk_1 ON driver (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE driver ADD CONSTRAINT FK_11667CD96B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE location CHANGE latitude latitude NUMERIC(9, 6) NOT NULL, CHANGE longitude longitude NUMERIC(9, 6) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rating DROP FOREIGN KEY FK_D88926226B3CA4B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rating DROP FOREIGN KEY FK_D88926223751C934
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rating DROP FOREIGN KEY FK_D88926226B3CA4B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rating DROP FOREIGN KEY FK_D88926223751C934
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rating CHANGE id_user id_user INT NOT NULL, CHANGE id_driver id_driver INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rating ADD CONSTRAINT rating_ibfk_1 FOREIGN KEY (id_driver) REFERENCES driver (id_driver) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rating ADD CONSTRAINT rating_ibfk_2 FOREIGN KEY (id_user) REFERENCES user (id_user) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_d88926223751c934 ON rating
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX rating_ibfk_1 ON rating (id_driver)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_d88926226b3ca4b ON rating
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX rating_ibfk_2 ON rating (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rating ADD CONSTRAINT FK_D88926226B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rating ADD CONSTRAINT FK_D88926223751C934 FOREIGN KEY (id_driver) REFERENCES driver (id_driver)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation DROP FOREIGN KEY FK_CE6064046B3CA4B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation DROP FOREIGN KEY FK_CE6064046B3CA4B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation CHANGE id_user id_user INT NOT NULL, CHANGE date date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation ADD CONSTRAINT reclamation_ibfk_1 FOREIGN KEY (id_user) REFERENCES user (id_user) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_ce6064046b3ca4b ON reclamation
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX reclamation_ibfk_1 ON reclamation (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation ADD CONSTRAINT FK_CE6064046B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relocation DROP FOREIGN KEY FK_3C7EAF9A5ADA84A2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relocation DROP FOREIGN KEY FK_3C7EAF9A5ADA84A2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relocation CHANGE id_reservation id_reservation INT NOT NULL, CHANGE date date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relocation ADD CONSTRAINT relocation_ibfk_1 FOREIGN KEY (id_reservation) REFERENCES reservation (id_reservation) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_3c7eaf9a5ada84a2 ON relocation
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX relocation_ibfk_1 ON relocation (id_reservation)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE relocation ADD CONSTRAINT FK_3C7EAF9A5ADA84A2 FOREIGN KEY (id_reservation) REFERENCES reservation (id_reservation)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request DROP FOREIGN KEY FK_3B978F9FE173B1B8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request DROP FOREIGN KEY FK_3B978F9F61C09F39
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request DROP FOREIGN KEY FK_3B978F9FB3049FE6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request DROP FOREIGN KEY FK_3B978F9FE173B1B8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request DROP FOREIGN KEY FK_3B978F9F61C09F39
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request DROP FOREIGN KEY FK_3B978F9FB3049FE6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request CHANGE status status VARCHAR(255) DEFAULT 'PENDING' NOT NULL, CHANGE request_date request_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request ADD CONSTRAINT fk_request_arrival_location FOREIGN KEY (id_arrival_location) REFERENCES location (id_location) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request ADD CONSTRAINT fk_request_client FOREIGN KEY (id_client) REFERENCES user (id_user) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request ADD CONSTRAINT fk_request_departure_location FOREIGN KEY (id_departure_location) REFERENCES location (id_location) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_3b978f9f61c09f39 ON request
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX fk_request_departure_location ON request (id_departure_location)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_3b978f9fb3049fe6 ON request
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX fk_request_arrival_location ON request (id_arrival_location)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_3b978f9fe173b1b8 ON request
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX fk_request_client ON request (id_client)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request ADD CONSTRAINT FK_3B978F9FE173B1B8 FOREIGN KEY (id_client) REFERENCES user (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request ADD CONSTRAINT FK_3B978F9F61C09F39 FOREIGN KEY (id_departure_location) REFERENCES location (id_location)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE request ADD CONSTRAINT FK_3B978F9FB3049FE6 FOREIGN KEY (id_arrival_location) REFERENCES location (id_location)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY FK_42C849557CD272FF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A41C6934
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY FK_42C849556E78AC6E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY FK_42C849556B3CA4B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY FK_42C849557CD272FF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A41C6934
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY FK_42C849556E78AC6E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY FK_42C849556B3CA4B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation CHANGE id_start_location id_start_location INT NOT NULL, CHANGE id_end_location id_end_location INT NOT NULL, CHANGE id_announcement id_announcement INT NOT NULL, CHANGE date date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE status status VARCHAR(255) DEFAULT 'CONFIRMED' NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT fk_reservation_user FOREIGN KEY (id_user) REFERENCES user (id_user) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT reservation_ibfk_3 FOREIGN KEY (id_announcement) REFERENCES announcement (id_announcement) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT reservation_ibfk_1 FOREIGN KEY (id_start_location) REFERENCES location (id_location) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT reservation_ibfk_2 FOREIGN KEY (id_end_location) REFERENCES location (id_location) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_42c849556b3ca4b ON reservation
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX fk_reservation_user ON reservation (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_42c849557cd272ff ON reservation
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX reservation_ibfk_1 ON reservation (id_start_location)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_42c84955a41c6934 ON reservation
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX reservation_ibfk_2 ON reservation (id_end_location)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_42c849556e78ac6e ON reservation
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX reservation_ibfk_3 ON reservation (id_announcement)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT FK_42C849557CD272FF FOREIGN KEY (id_start_location) REFERENCES location (id_location)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A41C6934 FOREIGN KEY (id_end_location) REFERENCES location (id_location)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT FK_42C849556E78AC6E FOREIGN KEY (id_announcement) REFERENCES announcement (id_announcement)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT FK_42C849556B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE response DROP FOREIGN KEY FK_3E7B0BFBD672A9F3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE response DROP FOREIGN KEY FK_3E7B0BFBD672A9F3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE response CHANGE id_reclamation id_reclamation INT NOT NULL, CHANGE date date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE response ADD CONSTRAINT response_ibfk_1 FOREIGN KEY (id_reclamation) REFERENCES reclamation (id_reclamation) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_3e7b0bfbd672a9f3 ON response
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX response_ibfk_1 ON response (id_reclamation)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE response ADD CONSTRAINT FK_3E7B0BFBD672A9F3 FOREIGN KEY (id_reclamation) REFERENCES reclamation (id_reclamation)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride DROP FOREIGN KEY FK_9B3D7CD0E50A26EF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride DROP FOREIGN KEY FK_9B3D7CD0D4A47FC0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride DROP FOREIGN KEY FK_9B3D7CD0E50A26EF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride DROP FOREIGN KEY FK_9B3D7CD0D4A47FC0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride CHANGE id_request id_request INT NOT NULL, CHANGE distance distance NUMERIC(5, 2) DEFAULT NULL, CHANGE price price NUMERIC(10, 2) DEFAULT NULL, CHANGE status status VARCHAR(255) DEFAULT 'ONGOING' NOT NULL, CHANGE ride_date ride_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride ADD CONSTRAINT ride_ibfk_3 FOREIGN KEY (id_request) REFERENCES request (id_request) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride ADD CONSTRAINT fk_id_taxi FOREIGN KEY (id_taxi) REFERENCES driver (id_driver) ON UPDATE CASCADE ON DELETE SET NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_9b3d7cd0e50a26ef ON ride
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX ride_ibfk_3 ON ride (id_request)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_9b3d7cd0d4a47fc0 ON ride
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX fk_id_taxi ON ride (id_taxi)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride ADD CONSTRAINT FK_9B3D7CD0E50A26EF FOREIGN KEY (id_request) REFERENCES request (id_request)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride ADD CONSTRAINT FK_9B3D7CD0D4A47FC0 FOREIGN KEY (id_taxi) REFERENCES driver (id_driver)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trip DROP FOREIGN KEY FK_7656F53B3751C934
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trip DROP FOREIGN KEY FK_7656F53BC51D4DF6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trip DROP FOREIGN KEY FK_7656F53B3751C934
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trip DROP FOREIGN KEY FK_7656F53BC51D4DF6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trip CHANGE id_driver id_driver INT NOT NULL, CHANGE id_vehicle id_vehicle INT NOT NULL, CHANGE departure_date departure_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE price_per_passenger price_per_passenger NUMERIC(5, 2) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trip ADD CONSTRAINT trip_ibfk_1 FOREIGN KEY (id_vehicle) REFERENCES vehicle (id_vehicle) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trip ADD CONSTRAINT trip_ibfk_2 FOREIGN KEY (id_driver) REFERENCES driver (id_driver) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_7656f53bc51d4df6 ON trip
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX trip_ibfk_1 ON trip (id_vehicle)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_7656f53b3751c934 ON trip
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX trip_ibfk_2 ON trip (id_driver)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trip ADD CONSTRAINT FK_7656F53B3751C934 FOREIGN KEY (id_driver) REFERENCES driver (id_driver)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trip ADD CONSTRAINT FK_7656F53BC51D4DF6 FOREIGN KEY (id_vehicle) REFERENCES vehicle (id_vehicle)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP FOREIGN KEY FK_8D93D649E45655E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP FOREIGN KEY FK_8D93D649E45655E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD name VARCHAR(100) NOT NULL, DROP first_name, DROP last_name, CHANGE email email VARCHAR(50) NOT NULL, CHANGE phone_number phone_number VARCHAR(15) NOT NULL, CHANGE account_status account_status VARCHAR(255) DEFAULT 'ACTIVE' NOT NULL, CHANGE status status VARCHAR(255) DEFAULT 'OFFLINE' NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD CONSTRAINT user_ibfk_1 FOREIGN KEY (id_location) REFERENCES location (id_location) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX email ON user (email)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX phone_number ON user (phone_number)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_8d93d649e45655e ON user
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX user_ibfk_1 ON user (id_location)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD CONSTRAINT FK_8D93D649E45655E FOREIGN KEY (id_location) REFERENCES location (id_location)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E4863751C934
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E4863751C934
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE vehicle CHANGE id_driver id_driver INT NOT NULL, CHANGE registration registration VARCHAR(20) NOT NULL, CHANGE color color VARCHAR(50) NOT NULL, CHANGE model model VARCHAR(100) NOT NULL, CHANGE brand brand VARCHAR(50) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE vehicle ADD CONSTRAINT vehicle_ibfk_1 FOREIGN KEY (id_driver) REFERENCES driver (id_driver) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX registration ON vehicle (registration)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_1b80e4863751c934 ON vehicle
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX vehicle_ibfk_1 ON vehicle (id_driver)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E4863751C934 FOREIGN KEY (id_driver) REFERENCES driver (id_driver)
        SQL);
    }
}
