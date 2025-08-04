#!/bin/bash

# SSL Certificate Management Script for Foro Académico
# This script helps manage SSL certificates for development and production

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SSL_DIR="${SCRIPT_DIR}/ssl"
NGINX_DIR="${SCRIPT_DIR}/nginx"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

# Function to create SSL directory
create_ssl_directory() {
    log "Creating SSL directory..."
    mkdir -p "$SSL_DIR"
    chmod 700 "$SSL_DIR"
}

# Function to generate self-signed certificates for development
generate_dev_certificates() {
    log "Generating self-signed certificates for development..."
    
    create_ssl_directory
    
    # Generate private key
    openssl genrsa -out "$SSL_DIR/key.pem" 2048
    
    # Generate certificate signing request
    openssl req -new -key "$SSL_DIR/key.pem" -out "$SSL_DIR/cert.csr" -subj "/C=ES/ST=Madrid/L=Madrid/O=Foro Academico/OU=Development/CN=localhost"
    
    # Generate self-signed certificate
    openssl x509 -req -days 365 -in "$SSL_DIR/cert.csr" -signkey "$SSL_DIR/key.pem" -out "$SSL_DIR/cert.pem"
    
    # Generate DH parameters
    log "Generating DH parameters (this may take a while)..."
    openssl dhparam -out "$SSL_DIR/dhparam.pem" 2048
    
    # Set proper permissions
    chmod 600 "$SSL_DIR/key.pem"
    chmod 644 "$SSL_DIR/cert.pem"
    chmod 644 "$SSL_DIR/dhparam.pem"
    
    # Cleanup
    rm -f "$SSL_DIR/cert.csr"
    
    log "Self-signed certificates generated successfully!"
    info "Certificate: $SSL_DIR/cert.pem"
    info "Private Key: $SSL_DIR/key.pem"
    info "DH Parameters: $SSL_DIR/dhparam.pem"
}

# Function to install production certificates
install_prod_certificates() {
    local cert_file="$1"
    local key_file="$2"
    
    if [[ -z "$cert_file" || -z "$key_file" ]]; then
        error "Usage: install_prod_certificates <cert_file> <key_file>"
        return 1
    fi
    
    if [[ ! -f "$cert_file" ]]; then
        error "Certificate file not found: $cert_file"
        return 1
    fi
    
    if [[ ! -f "$key_file" ]]; then
        error "Key file not found: $key_file"
        return 1
    fi
    
    log "Installing production certificates..."
    
    create_ssl_directory
    
    # Copy certificates
    cp "$cert_file" "$SSL_DIR/cert.pem"
    cp "$key_file" "$SSL_DIR/key.pem"
    
    # Generate DH parameters if not exists
    if [[ ! -f "$SSL_DIR/dhparam.pem" ]]; then
        log "Generating DH parameters for production..."
        openssl dhparam -out "$SSL_DIR/dhparam.pem" 2048
    fi
    
    # Set proper permissions
    chmod 600 "$SSL_DIR/key.pem"
    chmod 644 "$SSL_DIR/cert.pem"
    chmod 644 "$SSL_DIR/dhparam.pem"
    
    log "Production certificates installed successfully!"
}

# Function to verify certificates
verify_certificates() {
    log "Verifying SSL certificates..."
    
    if [[ ! -f "$SSL_DIR/cert.pem" ]]; then
        error "Certificate file not found: $SSL_DIR/cert.pem"
        return 1
    fi
    
    if [[ ! -f "$SSL_DIR/key.pem" ]]; then
        error "Key file not found: $SSL_DIR/key.pem"
        return 1
    fi
    
    # Verify certificate
    info "Certificate information:"
    openssl x509 -in "$SSL_DIR/cert.pem" -text -noout | grep -E "(Subject:|Issuer:|Not Before:|Not After:)"
    
    # Verify private key matches certificate
    cert_hash=$(openssl x509 -noout -modulus -in "$SSL_DIR/cert.pem" | openssl md5)
    key_hash=$(openssl rsa -noout -modulus -in "$SSL_DIR/key.pem" | openssl md5)
    
    if [[ "$cert_hash" == "$key_hash" ]]; then
        log "Certificate and private key match!"
    else
        error "Certificate and private key do not match!"
        return 1
    fi
}

# Function to backup certificates
backup_certificates() {
    local backup_dir="${SSL_DIR}/backup/$(date +%Y%m%d_%H%M%S)"
    
    if [[ ! -f "$SSL_DIR/cert.pem" && ! -f "$SSL_DIR/key.pem" ]]; then
        warning "No certificates found to backup."
        return 0
    fi
    
    log "Creating backup of SSL certificates..."
    mkdir -p "$backup_dir"
    
    if [[ -f "$SSL_DIR/cert.pem" ]]; then
        cp "$SSL_DIR/cert.pem" "$backup_dir/"
    fi
    
    if [[ -f "$SSL_DIR/key.pem" ]]; then
        cp "$SSL_DIR/key.pem" "$backup_dir/"
    fi
    
    if [[ -f "$SSL_DIR/dhparam.pem" ]]; then
        cp "$SSL_DIR/dhparam.pem" "$backup_dir/"
    fi
    
    log "Certificates backed up to: $backup_dir"
}

# Function to clean old certificates
clean_certificates() {
    log "Cleaning SSL certificates..."
    
    if [[ -d "$SSL_DIR" ]]; then
        rm -rf "$SSL_DIR"
        log "SSL certificates cleaned successfully!"
    else
        info "No SSL certificates found to clean."
    fi
}

# Function to show SSL status
show_status() {
    log "SSL Certificate Status:"
    
    if [[ -f "$SSL_DIR/cert.pem" ]]; then
        info "✓ Certificate file exists: $SSL_DIR/cert.pem"
        
        # Show expiration date
        expiry_date=$(openssl x509 -enddate -noout -in "$SSL_DIR/cert.pem" | cut -d= -f2)
        info "  Expires: $expiry_date"
        
        # Check if certificate is expiring soon (30 days)
        if openssl x509 -checkend 2592000 -noout -in "$SSL_DIR/cert.pem" >/dev/null 2>&1; then
            info "  Status: Valid"
        else
            warning "  Status: Expiring soon or expired!"
        fi
    else
        warning "✗ Certificate file not found"
    fi
    
    if [[ -f "$SSL_DIR/key.pem" ]]; then
        info "✓ Private key file exists: $SSL_DIR/key.pem"
    else
        warning "✗ Private key file not found"
    fi
    
    if [[ -f "$SSL_DIR/dhparam.pem" ]]; then
        info "✓ DH parameters file exists: $SSL_DIR/dhparam.pem"
    else
        warning "✗ DH parameters file not found"
    fi
}

# Function to show help
show_help() {
    echo "SSL Certificate Management Script for Foro Académico"
    echo ""
    echo "Usage: $0 [COMMAND] [OPTIONS]"
    echo ""
    echo "Commands:"
    echo "  dev                              Generate self-signed certificates for development"
    echo "  prod <cert_file> <key_file>      Install production certificates"
    echo "  verify                           Verify existing certificates"
    echo "  backup                           Backup current certificates"
    echo "  clean                            Remove all certificates"
    echo "  status                           Show certificate status"
    echo "  help                             Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 dev                           # Generate development certificates"
    echo "  $0 prod /path/to/cert.pem /path/to/key.pem"
    echo "  $0 status                        # Check certificate status"
    echo ""
}

# Main script logic
case "${1:-help}" in
    "dev")
        generate_dev_certificates
        ;;
    "prod")
        install_prod_certificates "$2" "$3"
        ;;
    "verify")
        verify_certificates
        ;;
    "backup")
        backup_certificates
        ;;
    "clean")
        clean_certificates
        ;;
    "status")
        show_status
        ;;
    "help"|*)
        show_help
        ;;
esac
