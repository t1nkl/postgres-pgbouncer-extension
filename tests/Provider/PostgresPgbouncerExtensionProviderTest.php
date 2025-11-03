<?php

declare(strict_types=1);

namespace Tests\Provider;

use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use PDO;
use PHPUnit\Framework\TestCase;
use PostgresPgbouncerExtension\Database\PostgresConnection as CustomPostgresConnection;
use PostgresPgbouncerExtension\PostgresPgbouncerExtensionProvider;
use ReflectionClass;

class PostgresPgbouncerExtensionProviderTest extends TestCase
{
    private array $originalResolvers = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->captureResolvers();

        // Reset config
        config(['database.default' => 'pgsql']);
        config(['database.connections.pgsql.options' => []]);

        // Ensure there is a container instance for the provider (not strictly necessary for register())
        if (!Container::getInstance()) {
            Container::setInstance(new Container());
        }
    }

    protected function tearDown(): void
    {
        $this->restoreResolvers();
        parent::tearDown();
    }

    private function captureResolvers(): void
    {
        $ref = new ReflectionClass(Connection::class);
        $prop = $ref->getProperty('resolvers');
        $prop->setAccessible(true);
        $this->originalResolvers = $prop->getValue();
        // Reset to empty for test isolation
        $prop->setValue(null, []);
    }

    private function restoreResolvers(): void
    {
        $ref = new ReflectionClass(Connection::class);
        $prop = $ref->getProperty('resolvers');
        $prop->setAccessible(true);
        $prop->setValue(null, $this->originalResolvers);
    }

    public function testRegisterSetsResolverWhenAssociativeEmulatePreparesTrue(): void
    {
        // Associative style: [PDO::ATTR_EMULATE_PREPARES => true]
        config(['database.connections.pgsql.options' => [PDO::ATTR_EMULATE_PREPARES => true]]);

        $provider = new PostgresPgbouncerExtensionProvider(Container::getInstance());
        $provider->register();

        $resolver = Connection::getResolver('pgsql');
        $this->assertIsCallable($resolver);

        $conn = $resolver(null, 'db', '', []);
        $this->assertInstanceOf(CustomPostgresConnection::class, $conn);
    }

    public function testRegisterSetsResolverWhenNumericArrayContainsEmulatePrepares(): void
    {
        // Numeric style: [PDO::ATTR_EMULATE_PREPARES]
        config(['database.connections.pgsql.options' => [PDO::ATTR_PERSISTENT, PDO::ATTR_EMULATE_PREPARES]]);

        $provider = new PostgresPgbouncerExtensionProvider(Container::getInstance());
        $provider->register();

        $resolver = Connection::getResolver('pgsql');
        $this->assertIsCallable($resolver);
    }

    public function testRegisterTreatsTruthyNonBooleanAsEnabled(): void
    {
        // 1 should be treated as truthy
        config(['database.connections.pgsql.options' => [PDO::ATTR_EMULATE_PREPARES => 1]]);

        $provider = new PostgresPgbouncerExtensionProvider(Container::getInstance());
        $provider->register();

        $resolver = Connection::getResolver('pgsql');
        $this->assertIsCallable($resolver);
    }

    public function testRegisterDoesNotSetResolverForFalsyVariants(): void
    {
        foreach ([0, '', null] as $falsy) {
            $this->captureResolvers();
            config(['database.connections.pgsql.options' => [PDO::ATTR_EMULATE_PREPARES => $falsy]]);

            $provider = new PostgresPgbouncerExtensionProvider(Container::getInstance());
            $provider->register();

            $resolver = Connection::getResolver('pgsql');
            $this->assertNull($resolver);
            $this->restoreResolvers();
        }
    }

    public function testRegisterDoesNotSetResolverWhenOptionsIsNotArray(): void
    {
        foreach ([null, 'string'] as $notArray) {
            $this->captureResolvers();
            config(['database.connections.pgsql.options' => $notArray]);

            $provider = new PostgresPgbouncerExtensionProvider(Container::getInstance());
            $provider->register();

            $resolver = Connection::getResolver('pgsql');
            $this->assertNull($resolver);
            $this->restoreResolvers();
        }
    }

    public function testRegisterRespectsDefaultConnectionNamespace(): void
    {
        // Switch default connection to mysql and provide its options
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.options' => [PDO::ATTR_EMULATE_PREPARES => true]]);

        $provider = new PostgresPgbouncerExtensionProvider(Container::getInstance());
        $provider->register();

        // Even though default is mysql, resolver for pgsql should be set when option is enabled
        $resolver = Connection::getResolver('pgsql');
        $this->assertIsCallable($resolver);
    }

    public function testRegisterIdempotency(): void
    {
        config(['database.connections.pgsql.options' => [PDO::ATTR_EMULATE_PREPARES => true]]);

        $provider = new PostgresPgbouncerExtensionProvider(Container::getInstance());
        $provider->register();
        $provider->register();

        $resolver = Connection::getResolver('pgsql');
        $this->assertIsCallable($resolver);
    }

    public function testRegisterDoesNotSetResolverWhenEmulatePreparesFalse(): void
    {
        config(['database.connections.pgsql.options' => [PDO::ATTR_EMULATE_PREPARES => false]]);

        $provider = new PostgresPgbouncerExtensionProvider(Container::getInstance());
        $provider->register();

        $resolver = Connection::getResolver('pgsql');
        $this->assertNull($resolver);
    }

    public function testRegisterDoesNotSetResolverWhenOptionMissing(): void
    {
        config(['database.connections.pgsql.options' => []]);

        $provider = new PostgresPgbouncerExtensionProvider(Container::getInstance());
        $provider->register();

        $resolver = Connection::getResolver('pgsql');
        $this->assertNull($resolver);
    }
}
