<?php

namespace RedisInAction\Ch07;

use RedisInAction\Test\TestCase;

class Ch06Test extends TestCase
{
    const CONTENT = 'this is some random content, look at how it is indexed.';

    protected function setUp()
    {
        parent::setUp();

        $this->conn->flushdb();
    }

    protected function tearDown()
    {
        $this->conn->flushdb();

        parent::tearDown();
    }

    public function test_index_document()
    {
        self::pprint("We're tokenizing some content...");
        $tokens = tokenize(self::CONTENT);
        self::pprint("Those tokens are:");
        self::pprint($tokens);
        $this->assertNotEmpty($tokens);

        self::pprint("And now we are indexing that content...");
        $r = index_document($this->conn, 'test', self::CONTENT);
        $this->assertEquals(count($tokens), $r);

        foreach ($tokens as $t) {
            $this->assertEquals(['test'], $this->conn->smembers('idx:' . $t));
        }
    }

    public function test_set_operations()
    {
        index_document($this->conn, 'test', self::CONTENT);

        $r = intersect($this->conn, ['content', 'indexed']);
        $this->assertEquals(['test'], $this->conn->smembers('idx:' . $r));

        $r = intersect($this->conn, ['content', 'ignored']);
        $this->assertEquals([], $this->conn->smembers('idx:' . $r));

        $r = union($this->conn, ['content', 'ignored']);
        $this->assertEquals(['test'], $this->conn->smembers('idx:' . $r));

        $r = difference($this->conn, ['content', 'ignored']);
        $this->assertEquals(['test'], $this->conn->smembers('idx:' . $r));

        $r = difference($this->conn, ['content', 'indexed']);
        $this->assertEquals([], $this->conn->smembers('idx:' . $r));
    }

    public function test_parse_query()
    {
        $query = 'test query without stopwords';
        $this->assertEquals(
            [
                array_map(function ($x) { return [$x]; }, explode(' ', $query)),
                []
            ],
            parse($query)
        );

        $query = 'test +query without -stopwords';
        $this->assertEquals(
            [
                [ ['test', 'query'], ['without'] ],
                ['stopwords']
            ],
            parse($query)
        );
    }

    public function test_parse_and_search()
    {
        self::pprint("And now we are testing search...");

        index_document($this->conn, 'test', self::CONTENT);

        $r = parse_and_search($this->conn, 'content');
        $this->assertEquals(['test'], $this->conn->smembers('idx:' . $r));

        $r = parse_and_search($this->conn, 'content indexed random');
        $this->assertEquals(['test'], $this->conn->smembers('idx:' . $r));

        $r = parse_and_search($this->conn, 'content +indexed random');
        $this->assertEquals(['test'], $this->conn->smembers('idx:' . $r));

        $r = parse_and_search($this->conn, 'content indexed +random');
        $this->assertEquals(['test'], $this->conn->smembers('idx:' . $r));

        $r = parse_and_search($this->conn, 'content indexed -random');
        $this->assertEquals([], $this->conn->smembers('idx:' . $r));

        self::pprint("Which passed!");
    }

    public function test_search_with_sort()
    {
        self::pprint("And now let's test searching with sorting...");

        index_document($this->conn, 'test', self::CONTENT);
        index_document($this->conn, 'test2', self::CONTENT);
        $this->conn->hmset('kb:doc:test', ['updated' => 12345, 'id' => 10]);
        $this->conn->hmset('kb:doc:test2', ['updated' => 54321, 'id' => 1]);

        $r = search_and_sort($this->conn, 'content');
        $this->assertEquals(['test2', 'test'], $r[1]);

        $r = search_and_sort($this->conn, 'content', null, 300, '-id');
        $this->assertEquals(['test', 'test2'], $r[1]);

        self::pprint("Which passed!");
    }
}