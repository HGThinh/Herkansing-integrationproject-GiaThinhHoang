FROM odoo:17.0

# Switch to root user to install packages
USER root

# Install pika Python package
RUN pip install pika

# Switch back to the odoo user
USER odoo
