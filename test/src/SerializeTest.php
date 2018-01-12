<?php

namespace Lint\Test;

use PHPUnit\Framework\TestCase;
use SimpleLint\Lint;

class SerializeTest extends TestCase
{
    public function testSerializeClass()
    {
        $src = 'class ABC {}';
        $dest = [
            ['type' => 'class', 'clause' => 'class ABC'],
        ];
        $this->assertStringToEntities($src, $dest);
    }

    public function testSerializeClassExtended()
    {
        $src = 'class ABC extends AB implements C{}';
        $dest = [
            ['type' => 'class', 'clause' => 'class ABC extends AB implements C'],
        ];
        $this->assertStringToEntities($src, $dest);
    }

    public function testSerializeClassExtended2()
    {
        $src = 'class ABC extends A implements B, C{}';
        $dest = [
            ['type' => 'class', 'clause' => 'class ABC extends A implements B implements C'],
        ];
        $this->assertStringToEntities($src, $dest);
    }

    public function testSerializeNamespaceClass()
    {
        $src = 'namespace N;class ABC extends A implements B, C{}';
        $dest = [
            ['type' => 'class', 'clause' => 'namespace N class ABC extends A implements B implements C'],
        ];
        $this->assertStringToEntities($src, $dest);
    }

    public function testSerializeNamespaceClass2()
    {
        $src = 'namespace N\\M;class ABC extends A implements B, C{}';
        $dest = [
            ['type' => 'class', 'clause' => 'namespace N\\M class ABC extends A implements B implements C'],
        ];
        $this->assertStringToEntities($src, $dest);
    }

    public function testSerializeUse()
    {
        $src = 'namespace N\\M;use U;';
        $dest = [
            ['type' => 'use', 'clause' => 'namespace N\\M use U'],
        ];
        $this->assertStringToEntities($src, $dest);
    }

    public function testSerializeUse2()
    {
        $src = 'namespace N\\M;use U\\U2;';
        $dest = [
            ['type' => 'use', 'clause' => 'namespace N\\M use U\\U2'],
        ];
        $this->assertStringToEntities($src, $dest);
    }

    public function testSerializeNamespaceClassConst()
    {
        $src = 'namespace N;class ABC extends A implements B, C{const THE_CONST=1;}';
        $dest = [
            ['type' => 'class', 'clause' => 'namespace N class ABC extends A implements B implements C'],
            ['type' => 'const', 'clause' => 'namespace N class ABC extends A implements B implements C { public THE_CONST'],
        ];
        $this->assertStringToEntities($src, $dest);
    }

    public function testSerializeNamespaceClassConst2()
    {
        $src = 'namespace N;class ABC extends A implements B, C{const THE_CONST=1;const THE_CONST2=2, THE_CONST3=3;}';
        $dest = [
            ['type' => 'class', 'clause' => 'namespace N class ABC extends A implements B implements C'],
            ['type' => 'const', 'clause' => 'namespace N class ABC extends A implements B implements C { public THE_CONST'],
            ['type' => 'const', 'clause' => 'namespace N class ABC extends A implements B implements C { public THE_CONST2'],
            ['type' => 'const', 'clause' => 'namespace N class ABC extends A implements B implements C { public THE_CONST3'],
        ];
        $this->assertStringToEntities($src, $dest);
    }

    public function testSerializeNamespaceClassProperty()
    {
        $src = 'namespace N;class ABC extends A implements B, C{public $property=1;}';
        $dest = [
            ['type' => 'class', 'clause' => 'namespace N class ABC extends A implements B implements C'],
            ['type' => 'property', 'clause' => 'namespace N class ABC extends A implements B implements C { public $property'],
        ];
        $this->assertStringToEntities($src, $dest);
    }

    public function testSerializeNamespaceClassProperty2()
    {
        $src = 'namespace N;class ABC extends A implements B, C{var $property=1;}';
        $dest = [
            ['type' => 'class', 'clause' => 'namespace N class ABC extends A implements B implements C'],
            ['type' => 'property', 'clause' => 'namespace N class ABC extends A implements B implements C { public $property'],
        ];
        $this->assertStringToEntities($src, $dest);
    }

    public function testSerializeNamespaceClassProperty3()
    {
        $src = 'namespace N;class ABC extends A implements B, C{private $property=1;}';
        $dest = [
            ['type' => 'class', 'clause' => 'namespace N class ABC extends A implements B implements C'],
            ['type' => 'property', 'clause' => 'namespace N class ABC extends A implements B implements C { private $property'],
        ];
        $this->assertStringToEntities($src, $dest);
    }

    public function testSerializeNamespaceClassProperty4()
    {
        $src = 'namespace N;class ABC extends A implements B, C{protected $property=1;}';
        $dest = [
            ['type' => 'class', 'clause' => 'namespace N class ABC extends A implements B implements C'],
            ['type' => 'property', 'clause' => 'namespace N class ABC extends A implements B implements C { protected $property'],
        ];
        $this->assertStringToEntities($src, $dest);
    }

    public function testSerializeNamespaceClassMethod()
    {
        $src = 'namespace N;class ABC {public function m($a, $b){}}';
        $dest = [
            ['type' => 'class', 'clause' => 'namespace N class ABC'],
            ['type' => 'param', 'clause' => 'namespace N class ABC { public function m ( $a'],
            ['type' => 'param', 'clause' => 'namespace N class ABC { public function m ( $b'],
            ['type' => 'function', 'clause' => 'namespace N class ABC { public function m ( )'],
        ];
        $this->assertStringToEntities($src, $dest);
    }

    public function testSerializeNamespaceClassMethod2()
    {
        $src = 'namespace N;class ABC {public function m(int $a, bool $b):int{}}';
        $dest = [
            ['type' => 'class', 'clause' => 'namespace N class ABC'],
            ['type' => 'param', 'clause' => 'namespace N class ABC { public function m ( int $a'],
            ['type' => 'param', 'clause' => 'namespace N class ABC { public function m ( bool $b'],
            ['type' => 'function', 'clause' => 'namespace N class ABC { public function m ( ) :int'],
        ];
        $this->assertStringToEntities($src, $dest);
    }

    public function testSerializeVar()
    {
        $src = '$prop = 2;';
        $dest = [
            ['type' => 'var', 'clause' => '$prop'],
        ];
        $this->assertStringToEntities($src, $dest);
    }

    public function testSerializeVar2()
    {
        $src = 'function D(){$prop = 2;}';
        $dest = [
            ['type' => 'var', 'clause' => 'function D ( ) { $prop'],
        ];
        $this->assertStringToEntities($src, $dest);
    }

    public function testSerializeVar3()
    {
        $src = 'namespace N;function D(){$prop = 2;}';
        $dest = [
            ['type' => 'var', 'clause' => 'function D ( ) { $prop'],
        ];
        $this->assertStringToEntities($src, $dest);
    }

    public function testSerializeVar4()
    {
        $src = 'namespace N;class ABC {public function m(int $a, bool $b):int{$prop = 2;}}';
        $dest = [
            ['type' => 'class', 'clause' => 'namespace N class ABC'],
            ['type' => 'param', 'clause' => 'namespace N class ABC { public function m ( int $a'],
            ['type' => 'param', 'clause' => 'namespace N class ABC { public function m ( bool $b'],
            ['type' => 'function', 'clause' => 'namespace N class ABC { public function m ( ) :int'],
            ['type' => 'var', 'clause' => 'function m ( ) :int { $prop'],
        ];
        $this->assertStringToEntities($src, $dest);
    }

    /**
     * @param $src
     * @param $dest
     */
    private function assertStringToEntities($src, $dest)
    {
        $entities = Lint::convertPhpFromStringToSerializedEntrities('<?php ' . $src);
        foreach ($entities as $index => $entity) {
            $expect = $dest[$index];
            $actual = ['type' => $entity->type, 'clause' => $entity->clause];
            $this->assertEquals($expect, $actual);
        }
    }
}
