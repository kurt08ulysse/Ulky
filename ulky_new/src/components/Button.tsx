import React from 'react';
import { Pressable, Text, View, ActivityIndicator, ViewStyle, PressableProps } from 'react-native';
import { colors, spacing, typography } from '@/theme';

interface ButtonProps extends PressableProps {
  variant?: 'primary' | 'secondary' | 'tertiary' | 'destructive';
  size?: 'sm' | 'md' | 'lg';
  disabled?: boolean;
  loading?: boolean;
  children: React.ReactNode;
}

export function Button({
  variant = 'primary',
  size = 'md',
  disabled = false,
  loading = false,
  children,
  style,
  ...rest
}: ButtonProps) {
  const variantStyles: Record<string, ViewStyle> = {
    primary: {
      backgroundColor: disabled ? colors.neutral[200] : colors.primary[600],
    },
    secondary: {
      backgroundColor: colors.neutral[100],
      borderWidth: 1,
      borderColor: colors.primary[600],
    },
    tertiary: {
      backgroundColor: 'transparent',
    },
    destructive: {
      backgroundColor: disabled ? colors.neutral[200] : colors.error[600],
    },
  };

  const sizeStyles: Record<string, ViewStyle> = {
    sm: {
      paddingHorizontal: spacing.md,
      paddingVertical: spacing.sm,
      minHeight: 36,
    },
    md: {
      paddingHorizontal: spacing.lg,
      paddingVertical: spacing.md,
      minHeight: 44,
    },
    lg: {
      paddingHorizontal: spacing.lg,
      paddingVertical: spacing.md,
      minHeight: 48,
    },
  };

  const textColor = {
    primary: disabled ? colors.text.disabled : colors.text.inverse,
    secondary: disabled ? colors.text.disabled : colors.primary[600],
    tertiary: colors.primary[600],
    destructive: disabled ? colors.text.disabled : colors.text.inverse,
  };

  return (
    <Pressable
      disabled={disabled || loading}
      style={[
        {
          borderRadius: 12,
          justifyContent: 'center',
          alignItems: 'center',
          ...variantStyles[variant],
          ...sizeStyles[size],
        },
        style,
      ]}
      {...rest}
    >
      {loading ? (
        <ActivityIndicator color={textColor[variant]} size="small" />
      ) : (
        <Text
          style={{
            ...typography.button,
            color: textColor[variant],
            opacity: disabled ? 0.6 : 1,
          }}
        >
          {children}
        </Text>
      )}
    </Pressable>
  );
}
