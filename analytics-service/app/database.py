from collections.abc import Iterator
from contextlib import contextmanager

from psycopg import Connection
from psycopg.rows import dict_row

from app.settings import settings


@contextmanager
def get_connection() -> Iterator[Connection]:
    with Connection.connect(settings.database_url, row_factory=dict_row) as connection:
        yield connection
