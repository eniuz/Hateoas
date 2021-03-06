<?php

namespace Hateoas\Tests\Representation\Factory;

use Hateoas\HateoasBuilder;
use Hateoas\Representation\Factory\PagerfantaFactory;
use Hateoas\Tests\TestCase;
use Hateoas\UrlGenerator\CallableUrlGenerator;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

class PagerfantaFactoryTest extends TestCase
{
    public function test()
    {
        $results = array(
            'Adrien',
            'William',
        );

        $pagerProphecy = $this->prophesize('Pagerfanta\Pagerfanta');
        $pagerProphecy->getCurrentPageResults()->willReturn($results);
        $pagerProphecy->getCurrentPage()->willReturn(2);
        $pagerProphecy->getMaxPerPage()->willReturn(20);
        $pagerProphecy->getNbPages()->willReturn(4);

        $factory = new PagerfantaFactory('p', 'l');
        $representation1 = $factory->create(
            $pagerProphecy->reveal(),
            'users',
            array(
                'query' => 'hateoas',
            ),
            $results
        );
        $representation2 = $factory->create(
            $pagerProphecy->reveal(),
            'users',
            array(
                'query' => 'hateoas',
            )
        );

        foreach (array($representation1, $representation2) as $representation) {
            $this
                ->object($representation)
                    ->isInstanceOf('Hateoas\Representation\PaginatedCollection')
                ->variable($representation->getPage())
                    ->isEqualTo(2)
                ->variable($representation->getLimit())
                    ->isEqualTo(20)
                ->variable($representation->getPages())
                    ->isEqualTo(4)
                ->array($representation->getParameters())
                    ->isEqualTo(array(
                        'query' => 'hateoas',
                        'p' => 2,
                        'l' => 20,
                    ))
                ->string($representation->getPageParameterName())
                    ->isEqualTo('p')
                ->string($representation->getLimitParameterName())
                    ->isEqualTo('l')
            ;
        }
    }

    public function testFunctional()
    {
        $hateoas = HateoasBuilder::create()
            ->setUrlGenerator(null, new CallableUrlGenerator(function ($route, array $parameters) {
                return $route . '?' . http_build_query($parameters);
            }))
            ->build();

        $factory    = new PagerfantaFactory();
        $pagerfanta = new Pagerfanta(new ArrayAdapter(array(
            'bim',
            'bam',
            'boom'
        )));

        $collection = $factory->create($pagerfanta, 'my_route');

        $this
            ->string($hateoas->serialize($collection, 'xml'))
            ->isEqualTo(
                <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<collection page="1" limit="10" pages="1">
  <entry><![CDATA[bim]]></entry>
  <entry><![CDATA[bam]]></entry>
  <entry><![CDATA[boom]]></entry>
  <link rel="self" href="my_route?page=1&amp;limit=10"/>
  <link rel="first" href="my_route?page=1&amp;limit=10"/>
  <link rel="last" href="my_route?page=1&amp;limit=10"/>
</collection>

XML
            );
    }
}
