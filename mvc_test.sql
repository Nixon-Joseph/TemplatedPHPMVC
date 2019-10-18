-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 18, 2019 at 03:56 AM
-- Server version: 5.7.26
-- PHP Version: 7.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mvc_test`
--

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
CREATE TABLE IF NOT EXISTS `posts` (
  `Uid` bigint(20) NOT NULL AUTO_INCREMENT,
  `Name` varchar(250) NOT NULL,
  `Title` varchar(250) NOT NULL,
  `Date` date NOT NULL,
  `Author` varchar(200) NOT NULL,
  `Snippet` varchar(500) NOT NULL,
  `Contents` text NOT NULL,
  `Category` bigint(20) NOT NULL,
  `Image` varchar(200) NOT NULL,
  `Featured` bit(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`Uid`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`Uid`, `Name`, `Title`, `Date`, `Author`, `Snippet`, `Contents`, `Category`, `Image`, `Featured`) VALUES
(1, 'New feature', 'New feature', '2013-12-14', 'Chris', '', ' <p>\r\n                Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Aenean lacinia bibendum nulla sed consectetur. Etiam porta\r\n                sem malesuada magna mollis euismod. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet\r\n                risus.\r\n            </p>\r\n            <ul>\r\n                <li>Praesent commodo cursus magna, vel scelerisque nisl consectetur et.</li>\r\n                <li>Donec id elit non mi porta gravida at eget metus.</li>\r\n                <li>Nulla vitae elit libero, a pharetra augue.</li>\r\n            </ul>\r\n            <p>\r\n                Etiam porta <em>sem malesuada magna</em> mollis euismod. Cras mattis consectetur purus sit amet fermentum. Aenean lacinia bibendum nulla sed\r\n                consectetur.\r\n            </p>\r\n            <p>Donec ullamcorper nulla non metus auctor fringilla. Nulla vitae elit libero, a pharetra augue.</p>', 0, '', b'0'),
(2, 'Another blog post', 'Another blog post', '2013-12-23', 'Jacob', '', '<p>\r\n                Cum sociis natoque penatibus et magnis <a href=\"#\">dis parturient montes</a>, nascetur ridiculus mus. Aenean eu leo quam. Pellentesque ornare\r\n                sem lacinia quam venenatis vestibulum. Sed posuere consectetur est at lobortis. Cras mattis consectetur purus sit amet fermentum.\r\n            </p>\r\n            <blockquote>\r\n                <p>\r\n                    Curabitur blandit tempus porttitor. <strong>Nullam quis risus eget urna mollis</strong> ornare vel eu leo. Nullam id dolor id nibh ultricies\r\n                    vehicula ut id elit.\r\n                </p>\r\n            </blockquote>\r\n            <p>\r\n                Etiam porta <em>sem malesuada magna</em> mollis euismod. Cras mattis consectetur purus sit amet fermentum. Aenean lacinia bibendum nulla sed\r\n                consectetur.\r\n            </p>\r\n            <p>\r\n                Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor. Duis mollis, est non commodo luctus, nisi erat porttitor ligula, eget\r\n                lacinia odio sem nec elit. Morbi leo risus, porta ac consectetur ac, vestibulum at eros.\r\n            </p>', 0, '', b'0'),
(3, 'Sample blog post', 'Sample blog post', '2014-01-01', 'Mark', '', '<p>\r\n                This blog post shows a few different types of content that\'s supported and styled with Bootstrap. Basic typography, images, and code are all\r\n                supported.\r\n            </p>\r\n            <hr>\r\n            <p>\r\n                Cum sociis natoque penatibus et magnis <a href=\"#\">dis parturient montes</a>, nascetur ridiculus mus. Aenean eu leo quam. Pellentesque ornare\r\n                sem lacinia quam venenatis vestibulum. Sed posuere consectetur est at lobortis. Cras mattis consectetur purus sit amet fermentum.\r\n            </p>\r\n            <blockquote>\r\n                <p>\r\n                    Curabitur blandit tempus porttitor. <strong>Nullam quis risus eget urna mollis</strong> ornare vel eu leo. Nullam id dolor id nibh ultricies\r\n                    vehicula ut id elit.\r\n                </p>\r\n            </blockquote>\r\n            <p>\r\n                Etiam porta <em>sem malesuada magna</em> mollis euismod. Cras mattis consectetur purus sit amet fermentum. Aenean lacinia bibendum nulla sed\r\n                consectetur.\r\n            </p>\r\n            <h2>Heading</h2>\r\n            <p>\r\n                Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor. Duis mollis, est non commodo luctus, nisi erat porttitor ligula, eget\r\n                lacinia odio sem nec elit. Morbi leo risus, porta ac consectetur ac, vestibulum at eros.\r\n            </p>\r\n            <h3>Sub-heading</h3>\r\n            <p>Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.</p>\r\n            <pre><code>Example code block</code></pre>\r\n            <p>\r\n                Aenean lacinia bibendum nulla sed consectetur. Etiam porta sem malesuada magna mollis euismod. Fusce dapibus, tellus ac cursus commodo, tortor\r\n                mauris condimentum nibh, ut fermentum massa.\r\n            </p>\r\n            <h3>Sub-heading</h3>\r\n            <p>\r\n                Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Aenean lacinia bibendum nulla sed consectetur. Etiam porta\r\n                sem malesuada magna mollis euismod. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet\r\n                risus.\r\n            </p>\r\n            <ul>\r\n                <li>Praesent commodo cursus magna, vel scelerisque nisl consectetur et.</li>\r\n                <li>Donec id elit non mi porta gravida at eget metus.</li>\r\n                <li>Nulla vitae elit libero, a pharetra augue.</li>\r\n            </ul>\r\n            <p>Donec ullamcorper nulla non metus auctor fringilla. Nulla vitae elit libero, a pharetra augue.</p>\r\n            <ol>\r\n                <li>Vestibulum id ligula porta felis euismod semper.</li>\r\n                <li>Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.</li>\r\n                <li>Maecenas sed diam eget risus varius blandit sit amet non magna.</li>\r\n            </ol>\r\n            <p>Cras mattis consectetur purus sit amet fermentum. Sed posuere consectetur est at lobortis.</p>', 0, '', b'0'),
(4, 'Post title', 'Post title', '2019-10-10', '', 'This is a wider card with supporting text below as a natural lead-in to additional content.', '', 1, '', b'1'),
(5, 'Featured post', 'Featured post', '2019-10-15', '', 'This is a wider card with supporting text below as a natural lead-in to additional content.', '', 2, '', b'1'),
(6, 'Title of a longer featured blog post', 'Title of a longer featured blog post', '2019-10-17', '', 'Multiple lines of text that form the lede, informing new readers quickly and efficiently about what\'s most interesting in this post\'s contents.', '', 0, '', b'1');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
